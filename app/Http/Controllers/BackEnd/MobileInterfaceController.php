<?php

namespace App\Http\Controllers\BackEnd;

use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Helpers\UploadFile;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\HomePage\SectionTitle;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;

class MobileInterfaceController extends Controller
{
  //mobile interface main page
  public function index(Request $request)
  {
    $language = Language::where('code', $request->language)->firstOrFail();
    $information['language'] = $language;
    return view('backend.mobile-interface.index',$information);
  }

  //general setting view and update function
  public function setting(Request $request)
  {
    $language = Language::where('code', $request->language)->firstOrFail();
    $data['language'] = $language;
    $data['data'] = DB::table('basic_settings')->select('mobile_favicon', 'mobile_app_logo', 'mobile_primary_colour', 'mobile_breadcrumb_overlay_opacity', 'mobile_breadcrumb_overlay_colour')
      ->first();
    $data['config'] = include(public_path('config.php'));
    return view('backend.mobile-interface.general-settings', $data);
  }

  public function settingUpdate(Request $request)
  {
    $bs = DB::table('basic_settings')->select('mobile_favicon', 'mobile_app_logo')->first();

    $rules = [
      'api_base_url' => 'required|url',
    ];

    if (is_null($bs->mobile_favicon)) {
      $rules['mobile_favicon'] = 'required|mimes:png,jpg,jpeg,svg';
    }
    if (is_null($bs->mobile_favicon)) {
      $rules['mobile_app_logo'] = 'required|mimes:png,jpg,jpeg,svg';
    }

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors())->withInput();
    }

    $publicConfig = include base_path('public/config.php');
    $publicConfig['PUBLIC_API_BASE'] = $request->api_base_url;
    $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
    file_put_contents(base_path('public/config.php'), $configContent);


    if ($request->hasFile('mobile_favicon')) {
      if (isset($bs->mobile_favicon)) {
        $favicon = UploadFile::update(public_path('assets/img/mobile-interface/'), $request->file('mobile_favicon'), $bs->mobile_favicon);
      } else {
        $favicon = UploadFile::store(public_path('assets/img/mobile-interface/'), $request->file('mobile_favicon'));
      }
    }

    if ($request->hasFile('mobile_app_logo')) {
      if (isset($bs->mobile_app_logo)) {
        $logo = UploadFile::update(public_path('assets/img/mobile-interface/'), $request->file('mobile_app_logo'), $bs->mobile_app_logo);
      } else {
        $logo = UploadFile::store(public_path('assets/img/mobile-interface/'), $request->file('mobile_app_logo'));
      }
    }

    DB::table('basic_settings')->updateOrInsert(
      ['uniqid' => 12345],
      [
        'mobile_favicon' => $favicon ?? $bs->mobile_favicon,
        'mobile_app_logo' => $logo ?? $bs->mobile_app_logo,
        'mobile_primary_colour' => $request->mobile_primary_colour,
        'mobile_breadcrumb_overlay_colour' => $request->mobile_breadcrumb_overlay_colour,
        'mobile_breadcrumb_overlay_opacity' => $request->mobile_breadcrumb_overlay_opacity,
      ]
    );

    return redirect()->back()->with('success', __('Updated Successfully'));
  }

  //payment gateways view and update function
  public function paymentGateways(Request $request)
  {
    $data['data'] = include(public_path('config.php'));
    $language = Language::where('code', $request->language)->firstOrFail();
    $data['language'] = $language;
    $gateways = [
      'paypal',
      'paystack',
      'flutterwave',
      'mercadopago',
      'mollie',
      'stripe',
      'authorize.net',
      'phonepe',
      'paytabs',
      'midtrans',
      'toyyibpay',
      'myfatoorah',
      'xendit',
      'monnify',
      'now_payments',
      'razorpay'
    ];

    foreach ($gateways as $gateway) {
      $key = str_replace('.', '_', $gateway);

      $data[$key] = OnlineGateway::where('keyword', $gateway)
        ->select('mobile_status', 'mobile_information')
        ->first();
    }
    return view('backend.mobile-interface.gateway', $data);
  }

  //plugins view function
  public function plugins(Request $request)
  {
    $data['data'] = DB::table('basic_settings')->select('firebase_admin_json', 'app_google_map_status')
      ->first();
    $language = Language::where('code', $request->language)->firstOrFail();
    $data['language'] = $language;
    return view('backend.mobile-interface.plugins', $data);
  }

  public function updateFirebase(Request $request)
  {
    $request->validate([
      'firebase_admin_json' => 'required|mimes:json',
    ], [
      'firebase_admin_json.required' => __('The admin sdk json file is required.'),
      'firebase_admin_json.mimes' => __('Only json files are supported.'),
    ]);

    $bs = DB::table('basic_settings')
      ->select('firebase_admin_json')
      ->where('uniqid', 12345)
      ->first();

    // if json file already exists and user wants to update it
    if ($request->hasFile('firebase_admin_json') && !is_null($bs->firebase_admin_json)) {
      $file = UploadFile::update(public_path('assets/file/'), $request->file('firebase_admin_json'), $bs->firebase_admin_json);
    }

    //if json file doesn't exist and user wants to upload it
    if ($request->hasFile('firebase_admin_json') && is_null($bs->firebase_admin_json)) {
      $file = UploadFile::store(public_path('assets/file/'), $request->file('firebase_admin_json'));
    }

    DB::table('basic_settings')->updateOrInsert(
      ['uniqid' => 12345],
      [
        'firebase_admin_json' => $request->hasFile('firebase_admin_json') ? $file : $bs->firebase_admin_json,
      ]
    );

    session()->flash('success', __('Updated successfully!'));
    return redirect()->back();
  }

  public function updateGeo(Request $request)
  {
    $rules = [
      'app_google_map_status' => 'required',
    ];
    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator);
    }

    DB::table('basic_settings')->updateOrInsert(
      ['uniqid' => 12345],
      [
        'app_google_map_status' => $request->app_google_map_status
      ]
    );

    Session::flash('success', 'Updated Successfully');
    return redirect()->back();
  }

  public function content(Request $request)
  {
    $language = Language::where('code', $request->language)->firstOrFail();
    $data['langs']  = Language::get();
    $data['data'] = SectionTitle::where('language_id', $language->id)->first();
    $data['language'] = $language ;
    return view('backend.mobile-interface.content', $data);
  }
  public function update(Request $request)
  {
    $rules = [
      'category_title' => 'required | max:255',
      'upcoming_event_title' => 'required | max:255',
      'features_title' => 'required | max:255'
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors())->withInput();
    }

    $language = Language::where('code', $request->language)->firstOrFail();

    $content = SectionTitle::where('Language_id', $language->id)->first();
    $content->category_title = $request->category_title;
    $content->upcoming_event_title = $request->upcoming_event_title;
    $content->features_title = $request->features_title;
    $content->save();

    session()->flash('success', __('Updated successfully'));
    return redirect()->back()->with('success', __('Updated Successfully'));
  }
}
