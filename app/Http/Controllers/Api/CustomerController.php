<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\CustomerController as FrontEndCustomerController;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\BasicSettings\PageHeading;
use App\Models\Customer;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\EventContent;
use App\Models\Language;
use App\Models\Organizer;
use App\Models\OrganizerInfo;
use App\Rules\MatchEmailRule;
use App\Traits\ApiFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use PHPMailer\PHPMailer\PHPMailer;
use Laravel\Socialite\Facades\Socialite;

class CustomerController extends Controller
{
  use ApiFormatTrait;

  private $admin_user_name;
  public function __construct()
  {
    $admin = Admin::select('username')->first();
    $this->admin_user_name = $admin->username;
  }

  /* ******************************
     * Show login page
     * ******************************/
  public function login(Request $request)
  {
    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_login_page_title')->first();
    $basic_settings = Basic::query()->select('google_recaptcha_status', 'google_recaptcha_site_key', 'google_recaptcha_secret_key', 'facebook_login_status', 'facebook_app_id', 'facebook_app_secret', 'google_login_status', 'google_client_id', 'google_client_secret', 'breadcrumb')->first();
    $basic_settings['breadcrumb'] = asset('assets/admin/img/' . $basic_settings->breadcrumb);

    $data['bs'] = $basic_settings;
    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  /* ********************************
     * Submit login for authentication
     * ********************************/
  public function loginSubmit(Request $request)
  {
    $rules = [
      'username' => 'required',
      'password' => 'required'
    ];

    $info = Basic::select('google_recaptcha_status')->first();
    if ($info->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }

    $messages = [];
    if ($info->google_recaptcha_status == 1) {
      $messages['g-recaptcha-response.required'] = 'Please verify that you are not a robot.';
      $messages['g-recaptcha-response.captcha'] = 'Captcha error! try again later or contact site admin.';
    }

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    // Attempt login manually using credentials
    $customer = Customer::where('username', $request->username)->first();

    if (!$customer || !Hash::check($request->password, $customer->password)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid credentials'
      ], 401);
    }

    if (is_null($customer->email_verified_at)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Please verify your email address.'
      ], 403);
    }

    if ($customer->status == 0) {
      return response()->json([
        'status' => 'error',
        'message' => 'Sorry, your account has been deactivated.'
      ], 403);
    }

    // Delete old tokens and create new one
    $customer->tokens()->where('name', 'customer-login')->delete();
    $token = $customer->createToken($request->device_name ?? 'unknown-device')->plainTextToken;

    // Add full photo URL if exists
    if (!empty($customer->photo)) {
      $customer->photo = asset('assets/admin/img/customer-profile/' . $customer->photo);
    }
    Auth::guard('sanctum')->user($customer);

    return response()->json([
      'status' => 'success',
      'customer' => $customer,
      'token' => $token
    ], 200);
  }


  /* ******************************
     * forget password
  * ******************************/

  public function forget_mail(Request $request)
  {
    $rules = [
      'email' => [
        'required',
        'email:rfc,dns',
        new MatchEmailRule('customers')
      ]
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $user = Customer::where('email', $request->email)->first();
    $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    DB::table('password_resets')->updateOrInsert(
      ['email' => $user->email],
      ['token' => Hash::make($token), 'created_at' => now()]
    );


    // first, get the mail template information from db
    $mailTemplate = MailTemplate::where('mail_type', 'reset_password')->first();
    $mailSubject = $mailTemplate->mail_subject;
    $mailBody = $mailTemplate->mail_body;

    // second, send a password reset link to user via email
    $info = DB::table('basic_settings')
      ->select('website_title', 'smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')
      ->first();

    $name = $user->name;
    $link = __("Your OTP: ") . $token;

    $mailBody = str_replace('{customer_name}', $name, $mailBody);
    $mailBody = str_replace('{password_reset_link}', $link, $mailBody);
    $mailBody = str_replace('{website_title}', $info->website_title, $mailBody);

    // initialize a new mail
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    // if smtp status == 1, then set some value for PHPMailer
    if ($info->smtp_status == 1) {
      $mail->isSMTP();
      $mail->Host       = $info->smtp_host;
      $mail->SMTPAuth   = true;
      $mail->Username   = $info->smtp_username;
      $mail->Password   = $info->smtp_password;

      if ($info->encryption == 'TLS') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      }

      $mail->Port       = $info->smtp_port;
    }

    // finally add other informations and send the mail
    try {
      $mail->setFrom($info->from_mail, $info->from_name);
      $mail->addAddress($request->email);

      $mail->isHTML(true);
      $mail->Subject = $mailSubject;
      $mail->Body = $mailBody;
      $mail->send();

      $status = true;
      $messages = 'A mail has been sent to your email address.';
    } catch (\Exception $e) {
      $status = false;
      $messages = 'Mail could not be sent!';
    }


    return response()->json([
      'success' => $status ?? false,
      'userEmail' => $user->email,
      'message' => $messages ?? "something went wrong!"
    ]);
  }

  /* ******************************
     * reset password
  * ******************************/


  public function reset_password_submit(Request $request)
  {
    $rules = [
      'email' => 'required|email',
      'code' => 'required',
      'new_password' => 'required|confirmed',
      'new_password_confirmation' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    // find the reset record by email
    $record = DB::table('password_resets')
      ->where('email', $request->email)
      ->first();

    if (!$record) {
      return response()->json([
        'status' => 'error',
        'message' => __('Invalid email or token')
      ], 400);
    }

    // check the token
    if (!Hash::check($request->code, $record->token)) {
      return response()->json([
        'status' => 'error',
        'message' => __('Invalid email or expired code')
      ], 400);
    }

    // update password
    Customer::where('email', $request->email)->update([
      'password' => Hash::make($request->new_password),
    ]);

    // delete reset record
    DB::table('password_resets')->where('email', $request->email)->delete();

    return response()->json([
      'status' => 'success',
      'message' => __('Password updated successfully')
    ]);
  }


  public function authentication_fail()
  {
    return response()->json([
      'success' => false,
      'message' => 'Unauthenticated.'
    ], 401);
  }

  /* ******************************
     * Show customer signup page
     * ******************************/
  public function signup(Request $request)
  {
    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_signup_page_title')->first();
    $basic_settings = Basic::query()->select('google_recaptcha_status', 'google_recaptcha_site_key', 'google_recaptcha_secret_key', 'facebook_login_status', 'facebook_app_id', 'facebook_app_secret', 'google_login_status', 'google_client_id', 'google_client_secret', 'breadcrumb')->first();
    $basic_settings['breadcrumb'] = asset('assets/admin/img/' . $basic_settings->breadcrumb);

    $data['bs'] = $basic_settings;
    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  /* **************************************
     * Request for sign up as a new customer
     * **************************************/
  public function signupSubmit(Request $request)
  {
    //validation rules
    $rules = [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required|email|unique:customers',
      'username' => [
        'required',
        'alpha_dash',
        "not_in:$this->admin_user_name",
        Rule::unique('customers', 'username')
      ],
      'password' => 'required|confirmed',
      'password_confirmation' => 'required'
    ];

    $info = Basic::select('google_recaptcha_status')->first();
    if ($info->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }

    $messages = [];

    if ($info->google_recaptcha_status == 1) {
      $messages['g-recaptcha-response.required'] = __('Please verify that you are not a robot.');
      $messages['g-recaptcha-response.captcha'] = __('Captcha error! try again later or contact site admin.');
    }

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    //validation end

    $in = $request->all();
    $in['password'] = Hash::make($request->password);
    // first, generate a random string
    $randStr = Str::random(20);

    // second, generate a token
    $token = md5($randStr . $request->fname . $request->email);

    $in['verification_token'] = $token;

    // send a mail to user for verify his/her email address
    $customer_controller = new FrontEndCustomerController();
    $customer_controller->sendVerificationMail($request, $token);
    $customer = Customer::create($in);

    return response()->json([
      'success' => true,
      'message' => __('A verification mail has been sent to your email address'),
      'data' => $customer
    ]);
  }

  /* ****************************
     * Customer Dashboard
     * ****************************/
  public function dashboard(Request $request)
  {
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_dashboard_page_title')->first();

    $data['authUser'] = $customer;
    $bookings = Booking::where('customer_id', $customer->id)
      ->OrderBy('id', 'desc')
      ->limit(10)
      ->get();

    $bookingsData = $bookings->map(function ($booking) use ($language) {
      $event_title = EventContent::where([
        ['event_id', $booking->event_id],
        ['language_id', $language->id]
      ])->pluck('title')->first();

      $thumbnail = Event::where('id', $booking->event_id)->pluck('thumbnail')->first();


      if (!empty($booking->organizer_id)) {
        $organizerInfo = OrganizerInfo::where([
          'language_id' => $language->id,
          'organizer_id' => $booking->organizer_id,
        ])->first();
      }
      $organizerName = isset($organizerInfo) && !is_null($organizerInfo) ? $organizerInfo->name : "";

      $booking->event_title =  $event_title;
      $booking->thumbnail =  asset('assets/admin/img/event/thumbnail/' . $thumbnail);
      $booking->organizer_name =  $organizerName;
      return $booking;
    });

    $data['bookings'] = $bookingsData;

    return response()->json([
      'success' => true,
      'data' => $data,
    ]);
  }

  /* ****************************
     * Customer bookings
     * ****************************/
  public function bookings(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    //  Detect language
    $locale = $request->header('Accept-Language');
    $language = $locale
      ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    //  Page title
    $data['page_title'] = PageHeading::where('language_id', $language->id)
      ->pluck('customer_booking_page_title')
      ->first();

    //  Get bookings
    $bookings = Booking::where('customer_id', $customer->id)
      ->orderBy('id', 'desc')
      ->get();

    //  Transform collection
    $bookingsData = $bookings->map(function ($booking) use ($language) {
      $event_title = EventContent::where([
        ['event_id', $booking->event_id],
        ['language_id', $language->id]
      ])->pluck('title')->first();

      $thumbnail = Event::where('id', $booking->event_id)->pluck('thumbnail')->first();


      if (!empty($booking->organizer_id)) {
        $organizerInfo = OrganizerInfo::where([
          'language_id' => $language->id,
          'organizer_id' => $booking->organizer_id,
        ])->first();
      }
      $organizerName = isset($organizerInfo) && !is_null($organizerInfo) ? $organizerInfo->name : "";

      $booking->event_title =  $event_title;
      $booking->thumbnail =  asset('assets/admin/img/event/thumbnail/' . $thumbnail);
      $booking->organizer_name =  $organizerName;
      return $booking;
    });

    //  Assign transformed bookings to data
    $data['bookings'] = $bookingsData;

    //  Return response
    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /* ****************************
     * Customer booking details
     * ****************************/
  public function booking_details(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    //  Get booking
    $booking = Booking::where([['customer_id', $customer->id], ['id', $request->booking_id]])->first();

    if (!$booking) {
      return response()->json([
        'success' => false,
        'message' => 'booking not found'
      ], 401);
    }

    //  Detect language
    $locale = $request->header('Accept-Language');
    $language = $locale
      ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    //  Page title
    $data['page_title'] = PageHeading::where('language_id', $language->id)
      ->pluck('customer_booking_details_page_title')
      ->first();


    //calculation total_paid
    $booking->total_paid = number_format($booking->price + $booking->tax, 2);
    // attached invoice with path
    $booking->invoice = !empty($booking->invoice) ? asset('assets/admin/file/invoices/'.$booking->invoice) : null;
    $data['booking'] = $booking;

    //organizer
    if (!is_null(@$booking->organizer_id)) {
      $organizer = Organizer::join('organizer_infos', 'organizers.id', '=', 'organizer_infos.organizer_id')
        ->where('organizer_id', $booking->organizer_id)
        ->first();
      $data['organizer'] = $this->format_organizer_data($organizer, 'organizer');
    } else {
      $admin = Admin::first();
      $data['organizer'] = $this->format_organizer_data($admin, 'admin');
    }

    //  Return response
    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /* **************************
     * Edit profile
     * **************************/
  public function edit_profile(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();

    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $customer_info = $customer;
    $customer_info->photo = asset('assets/admin/img/customer-profile/' . $customer_info->photo);

    $data['customer_info'] = $customer_info;

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }


  /* **************************
     * update profile
     * **************************/
  public function update_profile(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $rules = [
      'fname' => 'required',
      'lname' => 'required',
      'email' => [
        'required',
        'email',
        Rule::unique('customers', 'email')->ignore($customer->id)
      ],
      'username' => [
        'required',
        'alpha_dash',
        "not_in:$this->admin_user_name",
        Rule::unique('customers', 'username')->ignore($customer->id)
      ],
      'photo' => $request->hasFile('photo') ? 'mimes:jpg,jpeg,png' : ''
    ];

    $messages = [
      'fname.required' => 'The first name field is required.',
      'lname.required' => 'The last name field is required.',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $in = $request->all();
    $file = $request->file('photo');
    if ($file) {
      $extension = $file->getClientOriginalExtension();
      $directory = public_path('assets/admin/img/customer-profile/');
      $fileName = uniqid() . '.' . $extension;
      @mkdir($directory, 0775, true);
      $file->move($directory, $fileName);
      $in['photo'] = $fileName;
    }
    $id = $customer->id;
    Customer::find($id)->update($in);

    $customer_info = Customer::find($id);
    $customer_info->photo = asset('assets/admin/img/customer-profile/' . $customer_info->photo);

    $data['customer_info'] = $customer_info;

    return response()->json([
      'success' => true,
      'data'    => $data,
      'message' => __('Updated Successfully')
    ]);
  }

  public function updated_password(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }
    $rules = [
      'current_password' => 'required',
      'new_password' => 'required|confirmed',
      'new_password_confirmation' => 'required'
    ];

    $messages = [
      'new_password.confirmed' => __('Password confirmation does not match.'),
      'new_password_confirmation.required' => __('The confirm new password field is required.')
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $customer = Customer::where('id', $customer->id)->firstOrFail();
    if (!Hash::check($request->current_password, $customer->password)) {
      return response()->json([
        'success' => false,
        'errors' => ['current_password' => ['Current password is incorrect.']]
      ], 422);
    }

    $customer->update([
      'password' => Hash::make($request->new_password)
    ]);

    return response()->json([
      'status' => true,
      'message' => 'Password updated successfully'
    ]);
  }

  /* **************************
     * Logout customer
     * **************************/
  public function logoutSubmit(Request $request)
  {
    $request->user()->currentAccessToken()->delete();
    return response()->json([
      'status' => 'success',
      'message' => 'Logout successfully'
    ], 200);
  }
  /*
  * Login facebook
  */
  public function handleFacebookCallback()
  {
    return $this->authenticationViaProvider('facebook');
  }

  public function facebookRedirect()
  {
    return Socialite::driver('facebook')->redirect();
  }
  /*
  * Handle Google Login
  */
  public function handleGoogleCallback(Request $request)
  {
    return $this->authenticationViaProvider('google');
  }

  public function googleRedirect()
  {
    return Socialite::driver('google')->redirect();
  }

  public function authenticationViaProvider($driver)
  {
    try {

      $user = Socialite::driver($driver)->user();
      $isUser = Customer::where('provider_id', $user->id)->first();

      if ($isUser) {
        Auth::guard('sanctum')->login($isUser);
        return response()->json([
          'status' => true,
          'message' => __('Login Successfully!'),
          'redirect_url' => route('api.customers.dashboard')
        ]);
      } else {
        //get and insert image
        $avatar = $user->getAvatar();
        $fileContents = file_get_contents($avatar);

        $avatarName = $user->getId() . '.jpg';
        $path = public_path('assets/admin/img/customer-profile/');

        file_put_contents($path . $avatarName, $fileContents);

        $createUser = Customer::create([
          'photo' => $avatarName,
          'fname' => $user->name,
          'email' => $user->email,
          'username' => $user->id,
          'provider' => $driver,
          'provider_id' => $user->id,
          'password' => encrypt('123456'),
          'email_verified_at' => now()
        ]);

        Auth::guard('sanctum')->login($createUser);
        return response()->json([
          'status' => true,
          'message' => __('Login Successfully!'),
          'redirect_url' => route('api.customers.dashboard')
        ]);
      }
    } catch (\Exception $e) {
      return response()->json([
        'status' => false,
        'message' => $e->getMessage(),
        'redirect_url' => null,
      ]);
    }
  }

}
