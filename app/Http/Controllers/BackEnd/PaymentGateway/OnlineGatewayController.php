<?php

namespace App\Http\Controllers\BackEnd\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class OnlineGatewayController extends Controller
{
  public function index()
  {
    $gatewayInfo['paypal'] = OnlineGateway::where('keyword', 'paypal')->first();
    $gatewayInfo['instamojo'] = OnlineGateway::where('keyword', 'instamojo')->first();
    $gatewayInfo['paystack'] = OnlineGateway::where('keyword', 'paystack')->first();
    $gatewayInfo['flutterwave'] = OnlineGateway::where('keyword', 'flutterwave')->first();
    $gatewayInfo['razorpay'] = OnlineGateway::where('keyword', 'razorpay')->first();
    $gatewayInfo['mercadopago'] = OnlineGateway::where('keyword', 'mercadopago')->first();
    $gatewayInfo['mollie'] = OnlineGateway::where('keyword', 'mollie')->first();
    $gatewayInfo['stripe'] = OnlineGateway::where('keyword', 'stripe')->first();
    $gatewayInfo['paytm'] = OnlineGateway::where('keyword', 'paytm')->first();
    $gatewayInfo['midtrans'] = OnlineGateway::where('keyword', 'midtrans')->first();
    $gatewayInfo['iyzico'] = OnlineGateway::where('keyword', 'iyzico')->first();
    $gatewayInfo['paytabs'] = OnlineGateway::where('keyword', 'paytabs')->first();
    $gatewayInfo['toyyibpay'] = OnlineGateway::where('keyword', 'toyyibpay')->first();
    $gatewayInfo['phonepe'] = OnlineGateway::where('keyword', 'phonepe')->first();
    $gatewayInfo['yoco'] = OnlineGateway::where('keyword', 'yoco')->first();
    $gatewayInfo['xendit'] = OnlineGateway::where('keyword', 'xendit')->first();
    $gatewayInfo['myfatoorah'] = OnlineGateway::where('keyword', 'myfatoorah')->first();
    $gatewayInfo['perfect_money'] = OnlineGateway::where('keyword', 'perfect_money')->first();

    return view('backend.payment-gateways.online-gateways', $gatewayInfo);
  }

  public function updatePayPalInfo(Request $request)
  {
    $rules = [
      'paypal_status' => 'required',
      'paypal_sandbox_status' => 'required',
      'paypal_client_id' => 'required',
      'paypal_client_secret' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['sandbox_status'] = $request->paypal_sandbox_status;
    $information['client_id'] = $request->paypal_client_id;
    $information['client_secret'] = $request->paypal_client_secret;

    $paypalInfo = OnlineGateway::where('keyword', 'paypal')->first();

    //mobile app set config file
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['PAYPAL_CLIENT_ID'] = $request->paypal_client_id;
        $publicConfig['PAYPAL_SECRET'] = $request->paypal_client_secret;
        $publicConfig['PAYPAL_BASE'] = $request->paypal_sandbox_status == 1
          ? 'https://api-m.sandbox.paypal.com'
          : 'https://api-m.paypal.com';
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);
      }
      $paypalInfo->update([
        'mobile_information' => json_encode($information),
        'mobile_status' => $request->paypal_status
      ]);
    } else {
      $paypalInfo->update([
        'information' => json_encode($information),
        'status' => $request->paypal_status
      ]);
    }

    Session::flash('success', 'Updated Paypal Informaion Successfully');
    return redirect()->back();
  }

  public function updateInstamojoInfo(Request $request)
  {
    $rules = [
      'instamojo_status' => 'required',
      'instamojo_sandbox_status' => 'required',
      'instamojo_key' => 'required',
      'instamojo_token' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['sandbox_status'] = $request->instamojo_sandbox_status;
    $information['key'] = $request->instamojo_key;
    $information['token'] = $request->instamojo_token;

    $instamojoInfo = OnlineGateway::where('keyword', 'instamojo')->first();

    $instamojoInfo->update([
      'information' => json_encode($information),
      'status' => $request->instamojo_status
    ]);

    Session::flash('success', 'Updated Instamojo Informaion Successfully');

    return redirect()->back();
  }

  public function updatePaystackInfo(Request $request)
  {
    $rules = [
      'paystack_status' => 'required',
      'paystack_key' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['key'] = $request->paystack_key;

    $paystackInfo = OnlineGateway::where('keyword', 'paystack')->first();

    $paystackInfo->update([
      'information' => json_encode($information),
      'status' => $request->paystack_status
    ]);


    //mobile app set config file
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        //update public/config file for paystack info(used it only for apps)
        $publicConfig = include base_path('public/config.php');
        $publicConfig['PAYSTACK_SECRET_KEY'] = $request->paystack_key;
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);

        $paystackInfo->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->paystack_status
        ]);
      }
    } else {
      $paystackInfo->update([
        'information' => json_encode($information),
        'status' => $request->paystack_status
      ]);
    }

    Session::flash('success', 'Updated Paystack Informaion Successfully');

    return redirect()->back();
  }

  public function updateFlutterwaveInfo(Request $request)
  {

    $rules = [
      'flutterwave_status' => 'required',
      'flutterwave_public_key' => 'required',
      'flutterwave_secret_key' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['public_key'] = $request->flutterwave_public_key;
    $information['secret_key'] = $request->flutterwave_secret_key;

    $flutterwaveInfo = OnlineGateway::where('keyword', 'flutterwave')->first();
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      //mobile app set config file
      $publicConfig = base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['FLW_SECRET_KEY'] = $request->flutterwave_secret_key;
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);
      }

      $flutterwaveInfo->mobile_status = $request->flutterwave_status;
      $flutterwaveInfo->mobile_information = json_encode($information);
      $flutterwaveInfo->save();
    } else {
      $flutterwaveInfo->update([
        'information' => json_encode($information),
        'status' => $request->flutterwave_status
      ]);
      $array = [
        'FLW_PUBLIC_KEY' => $request->flutterwave_public_key,
        'FLW_SECRET_KEY' => $request->flutterwave_secret_key
      ];
      setEnvironmentValue($array);
      Artisan::call('config:clear');
    }

    Session::flash('success', 'Updated Flutterwave Informaion Successfully');
    return redirect()->back();
  }

  public function updateRazorpayInfo(Request $request)
  {
    $rules = [
      'razorpay_status' => 'required',
      'razorpay_key' => 'required',
      'razorpay_secret' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['key'] = $request->razorpay_key;
    $information['secret'] = $request->razorpay_secret;

    $razorpayInfo = OnlineGateway::where('keyword', 'razorpay')->first();
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $razorpayInfo->mobile_information = json_encode($information);
      $razorpayInfo->mobile_status = $request->razorpay_status;
      $razorpayInfo->save();
    }else{
      $razorpayInfo->update([
        'information' => json_encode($information),
        'status' => $request->razorpay_status
      ]);
    }

    //mobile app set config file

    Session::flash('success', 'Updated Razorpay Informaion Successfully');

    return redirect()->back();
  }

  public function updateMercadoPagoInfo(Request $request)
  {
    $rules = [
      'mercadopago_status' => 'required',
      'mercadopago_sandbox_status' => 'required',
      'mercadopago_token' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['sandbox_status'] = $request->mercadopago_sandbox_status;
    $information['token'] = $request->mercadopago_token;

    $mercadopagoInfo = OnlineGateway::where('keyword', 'mercadopago')->first();

    //mobile app set config file
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        //update public/config file for mercadopago info(used it only for apps)
        $publicConfig = include base_path('public/config.php');
        $publicConfig['MP_ACCESS_TOKEN'] = $request->mercadopago_token;
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);

        $mercadopagoInfo->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->mercadopago_status
        ]);
      }
    } else {

      $mercadopagoInfo->update([
        'information' => json_encode($information),
        'status' => $request->mercadopago_status
      ]);
    }

    Session::flash('success', 'Updated Mercadopago Informaion Successfully');

    return redirect()->back();
  }

  public function updateMollieInfo(Request $request)
  {
    $rules = [
      'mollie_status' => 'required',
      'mollie_key' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['key'] = $request->mollie_key;

    $mollieInfo = OnlineGateway::where('keyword', 'mollie')->first();
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['MOLLIE_API_KEY'] = $request->mollie_key;
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);
        $mollieInfo->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->mollie_status
        ]);
      }
    } else {
      $mollieInfo->update([
        'information' => json_encode($information),
        'status' => $request->mollie_status
      ]);
      $array = ['MOLLIE_KEY' => $request->mollie_key];
      setEnvironmentValue($array);
      Artisan::call('config:clear');
    }

    Session::flash('success', 'Updated Mollie Informaion Successfully');

    return redirect()->back();
  }

  public function updateStripeInfo(Request $request)
  {
    $rules = [
      'stripe_status' => 'required',
      'stripe_key' => 'required',
      'stripe_secret' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['key'] = $request->stripe_key;
    $information['secret'] = $request->stripe_secret;

    $stripeInfo = OnlineGateway::where('keyword', 'stripe')->first();



    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['STRIPE_SECRET_KEY'] = $request->stripe_secret;
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);
        $stripeInfo->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->stripe_status
        ]);
      }
    } else {
      $stripeInfo->update([
        'information' => json_encode($information),
        'status' => $request->stripe_status
      ]);

      $array = [
        'STRIPE_KEY' => $request->stripe_key,
        'STRIPE_SECRET' => $request->stripe_secret
      ];

      setEnvironmentValue($array);
      Artisan::call('config:clear');
    }

    Session::flash('success', 'Updated Stripe Informaion Successfully');

    return redirect()->back();
  }

  public function updatePaytmInfo(Request $request)
  {
    $rules = [
      'paytm_status' => 'required',
      'paytm_environment' => 'required',
      'paytm_merchant_key' => 'required',
      'paytm_merchant_mid' => 'required',
      'paytm_merchant_website' => 'required',
      'paytm_industry_type' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['environment'] = $request->paytm_environment;
    $information['merchant_key'] = $request->paytm_merchant_key;
    $information['merchant_mid'] = $request->paytm_merchant_mid;
    $information['merchant_website'] = $request->paytm_merchant_website;
    $information['industry_type'] = $request->paytm_industry_type;

    $paytmInfo = OnlineGateway::where('keyword', 'paytm')->first();

    $paytmInfo->update([
      'information' => json_encode($information),
      'status' => $request->paytm_status
    ]);

    $array = [
      'PAYTM_ENVIRONMENT' => $request->paytm_environment,
      'PAYTM_MERCHANT_KEY' => $request->paytm_merchant_key,
      'PAYTM_MERCHANT_ID' => $request->paytm_merchant_mid,
      'PAYTM_MERCHANT_WEBSITE' => $request->paytm_merchant_website,
      'PAYTM_INDUSTRY_TYPE' => $request->paytm_industry_type
    ];

    setEnvironmentValue($array);
    Artisan::call('config:clear');

    Session::flash('success', 'Updated Paytm Informaion Successfully');

    return redirect()->back();
  }

  public function updateMidtransInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'is_production' => 'required',
      'server_key' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['is_production'] = $request->is_production;
    $information['server_key'] = $request->server_key;

    $data = OnlineGateway::where('keyword', 'midtrans')->first();

    if (isset($request->is_mobile) && $request->is_mobile == 1) {
    $publicConfig =  base_path('public/config.php');
    if (file_exists($publicConfig)) {
      $publicConfig = include base_path('public/config.php');
      $publicConfig['MIDTRANS_SERVER_KEY'] = $request->server_key;
      $publicConfig['MIDTRANS_BASE'] = $request->is_production == 1 ? 'https://app.sandbox.midtrans.com/snap/v1/transactions' :
        'https://app.midtrans.com/snap/v1/transactions';
      $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
      file_put_contents(base_path('public/config.php'), $configContent);

      $data->update([
        'mobile_information' => json_encode($information),
        'mobile_status' => $request->status
      ]);
    }}
    else{
      $data->update([
        'information' => json_encode($information),
        'status' => $request->status
      ]);

    }

    Session::flash('success', 'Updated Midtrans Information Successfully');

    return redirect()->back();
  }

  public function updatePaytabsInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'country' => 'required',
      'server_key' => 'required',
      'profile_id' => 'required',
      'api_endpoint' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['server_key'] = $request->server_key;
    $information['profile_id'] = $request->profile_id;
    $information['country'] = $request->country;
    $information['api_endpoint'] = $request->api_endpoint;

    $data = OnlineGateway::where('keyword', 'paytabs')->first();

    $data->update([
      'information' => json_encode($information),
      'status' => $request->status
    ]);

    Session::flash('success', 'Updated Paytabs Information Successfully');

    return redirect()->back();
  }

  public function updateToyyibpayInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'sandbox_status' => 'required',
      'secret_key' => 'required',
      'category_code' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['sandbox_status'] = $request->sandbox_status;
    $information['secret_key'] = $request->secret_key;
    $information['category_code'] = $request->category_code;

    $data = OnlineGateway::where('keyword', 'toyyibpay')->first();
    if (isset($request->is_mobile) && $request->is_mobile == 1) {

      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['TOYYIBPAY_SECRET_KEY'] = $request->secret_key;
        $publicConfig['TOYYIBPAY_CATEGORY_CODE'] = $request->category_code;
        $publicConfig['TOYYIBPAY_BASE'] = $request->sandbox_status == 1 ? 'https://dev.toyyibpay.com' : 'https://www.toyyibpay.com';
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);
      }

      $data->update([
        'mobile_information' => json_encode($information),
        'mobile_status' => $request->status
      ]);

    }else{

      $data->update([
        'information' => json_encode($information),
        'status' => $request->status
      ]);
    }
    Session::flash('success', 'Updated Toyyibpay Information Successfully');


    return redirect()->back();
  }

  public function updateIyzicoInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'sandbox_status' => 'required',
      'api_key' => 'required',
      'secret_key' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['sandbox_status'] = $request->sandbox_status;
    $information['api_key'] = $request->api_key;
    $information['secret_key'] = $request->secret_key;

    $data = OnlineGateway::where('keyword', 'iyzico')->first();

    $data->update([
      'information' => json_encode($information),
      'status' => $request->status
    ]);
    Session::flash('success', 'Updated Iyzico Information Successfully');

    return redirect()->back();
  }
  public function updatePhonepeInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'sandbox_status' => 'required',
      'merchant_id' => 'required',
      'salt_key' => 'required',
      'salt_index' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['merchant_id'] = $request->merchant_id;
    $information['sandbox_status'] = $request->sandbox_status;
    $information['salt_key'] = $request->salt_key;
    $information['salt_index'] = $request->salt_index;

    $data = OnlineGateway::where('keyword', 'phonepe')->first();

    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['PHONEPE_MERCHANT_ID'] = $request->merchant_id;
        $publicConfig['PHONEPE_SALT_KEY'] = $request->salt_key;
        $publicConfig['PHONEPE_SALT_INDEX'] = $request->salt_index;
        $publicConfig['PHONEPE_BASE'] = $request->sandbox_status == 1 ? 'https://api-preprod.phonepe.com/apis/pg-sandbox' : 'https://api.phonepe.com/apis/hermes';
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);

        $data->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->status
        ]);
      }

    }else{
      $data->update([
        'information' => json_encode($information),
        'status' => $request->status
      ]);
    }


    Session::flash('success', 'Updated Phonepe Information Successfully');

    return redirect()->back();
  }
  public function updateYocoInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'secret_key' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['secret_key'] = $request->secret_key;

    $data = OnlineGateway::where('keyword', 'yoco')->first();

    $data->update([
      'information' => json_encode($information),
      'status' => $request->status
    ]);

    Session::flash('success', 'Updated Yoco Information Successfully');

    return redirect()->back();
  }
  public function updateXenditInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'secret_key' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information['secret_key'] = $request->secret_key;

    $data = OnlineGateway::where('keyword', 'xendit')->first();
    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['XENDIT_SECRET_KEY'] = $request->secret_key;
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);
        $data->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->status
        ]);
      }
    }else{
      $data->update([
        'information' => json_encode($information),
        'status' => $request->status
      ]);
      $array = [
        'XENDIT_SECRET_KEY' => $request->secret_key,
      ];
      setEnvironmentValue($array);
      Artisan::call('config:clear');
    }

    Session::flash('success', 'Updated Xendit Information Successfully');

    return redirect()->back();
  }
  public function updateMyFatoorahInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'sandbox_status' => 'required',
      'token' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information = [
      'token' => $request->token,
      'sandbox_status' => $request->sandbox_status
    ];

    $data = OnlineGateway::where('keyword', 'myfatoorah')->first();

    if (isset($request->is_mobile) && $request->is_mobile == 1) {

      $publicConfig =  base_path('public/config.php');
      if (file_exists($publicConfig)) {
        $publicConfig = include base_path('public/config.php');
        $publicConfig['MYFATOORAH_API_KEY'] = $request->token;
        $publicConfig['MYFATOORAH_BASE'] = $request->sandbox_status == 1
          ? 'https://apitest.myfatoorah.com'
          : 'https://api.myfatoorah.com';
        $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
        file_put_contents(base_path('public/config.php'), $configContent);

        $data->update([
          'mobile_information' => json_encode($information),
          'mobile_status' => $request->status
        ]);
      }
    }else{

      $data->update([
        'information' => json_encode($information),
        'status' => $request->status
      ]);
      $array = [
        'MYFATOORAH_TOKEN' => $request->token,
        'MYFATOORAH_CALLBACK_URL' => route('myfatoorah_callback'),
        'MYFATOORAH_ERROR_URL' => route('myfatoorah_cancel'),
      ];
      setEnvironmentValue($array);
      Artisan::call('config:clear');

    }

    Session::flash('success', 'Updated Xendit Information Successfully');

    return redirect()->back();
  }

  public function updatePerfectMoneyInfo(Request $request)
  {
    $rules = [
      'status' => 'required',
      'perfect_money_wallet_id' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $information = [
      'perfect_money_wallet_id' => $request->perfect_money_wallet_id
    ];

    $data = OnlineGateway::where('keyword', 'perfect_money')->first();

    $data->update([
      'information' => json_encode($information),
      'status' => $request->status
    ]);

    Session::flash('success', 'Updated Perfect Money Information Successfully');

    return redirect()->back();
  }
  /**
   * update monnify info
   */
  public function updateMonify(Request $request)
  {
    $data = OnlineGateway::where('keyword', 'monnify')->first();

    $information = [
      "sandbox_status" => $request->sandbox_status,
      "api_key" => $request->api_key,
      "secret_key" => $request->secret_key,
      "wallet_account_number" => $request->wallet_account_number
    ];


    if (isset($request->is_mobile) && $request->is_mobile == 1) {
      //update public/config file for monnify info(used it only for apps)
      $publicConfig = include base_path('public/config.php');
      $publicConfig['MONNIFY_API_KEY'] = $request->api_key;
      $publicConfig['MONNIFY_SECRET_KEY'] = $request->secret_key;
      $publicConfig['MONNIFY_CONTRACT_CODE'] = $request->wallet_account_number;
      $publicConfig['MONNIFY_BASE'] = $request->sandbox_status == 1 ? 'https://sandbox.monnify.com' : 'https://api.monnify.com';
      $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
      file_put_contents(base_path('public/config.php'), $configContent);

      $data->mobile_status = $request->status;
      $data->mobile_information = json_encode($information);
      $data->save();
    } else {
      $data->status = $request->status;
      $data->information = json_encode($information);
      $data->save();
    }

    session()->flash('success', __('Updated Successfully'));
    return back();
  }

  /**
   * update nowpayments info
   */
  public function updateNowPayments(Request $request)
  {
    $rules = [
      'status' => 'required',
      'api_key' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors());
    }

    $nowPaymentsInfo = OnlineGateway::where('keyword', 'now_payments')->first();
    $information['api_key'] = $request->api_key;


    if (isset($request->is_mobile) && $request->is_mobile == 1) {

      //update public/config file for now_payments info(used it only for apps)
      $publicConfig = include base_path('public/config.php');
      $publicConfig['NOWPAYMENTS_API_KEY'] = $request->api_key;
      $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
      file_put_contents(base_path('public/config.php'), $configContent);


      $nowPaymentsInfo->update([
        'mobile_information' => json_encode($information),
        'mobile_status' => $request->status
      ]);
    } else {
      $nowPaymentsInfo->update([
        'information' => json_encode($information),
        'status' => $request->status
      ]);
    }


    session()->flash('success', __("NowPayments's information updated successfully!"));
    return redirect()->back();
  }

}
