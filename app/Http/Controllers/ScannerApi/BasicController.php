<?php

namespace App\Http\Controllers\ScannerApi;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BasicController extends Controller
{
  /* *****************************
     * basic data
    * *****************************/
  public function getBasic()
  {

    $basicData = DB::table('basic_settings')
      ->select('primary_color', 'mobile_app_logo', 'mobile_favicon', 'base_currency_text', 'base_currency_rate', 'tax', 'commission', 'shop_tax', 'mobile_primary_colour', 'mobile_breadcrumb_overlay_opacity', 'mobile_breadcrumb_overlay_colour', 'app_google_map_status', 'google_map_api_key', 'google_map_radius')
      ->first();

    $basicData->mobile_app_logo = asset('assets/img/mobile-interface/' . $basicData->mobile_app_logo);
    $basicData->mobile_favicon = asset('assets/img/mobile-interface/' . $basicData->mobile_favicon);

    $data['basic_data'] = $basicData;
    $data['languages'] = Language::all();

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  public function getLang($code)
  {
    $path = resource_path('lang/' . $code . '.json');
    $langData = json_decode(file_get_contents($path), true);
    return $langData;
  }
}
