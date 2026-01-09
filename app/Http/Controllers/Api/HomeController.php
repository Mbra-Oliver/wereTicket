<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Event\EventDates;
use App\Models\Event\Ticket;
use App\Models\Event\Wishlist;
use App\Models\Guest;
use App\Models\HomePage\Section;
use App\Models\Language;
use App\Models\Organizer;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
  /* ***************************
     * Home page
     * ***************************/
  public function index(Request $request)
  {
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    // get the sections of selected home version
    $sectionInfo = Section::first();
    $data['secInfo'] = $sectionInfo;

    $hero_section = $language->heroSec()->first();
   if(!empty($hero_section)){
     unset($hero_section['video_url']);
     unset($hero_section['image']);
   }
    $hero_section['background_image'] = !empty($hero_section) ? asset('assets/admin/img/hero-section/' . $hero_section->background_image) : "";
    $data['heroInfo'] = $hero_section;

    $data['secTitleInfo'] = $language->sectionTitle()->first();

    $categories = $language->event_category()->where('status', 1)->where('is_featured', '=', 'yes')->orderBy('serial_number', 'asc')
      ->get()
      ->map(function ($category) {
        $category->image = $category->image ? asset('assets/admin/img/event-category/' . $category->image) : null;
        return $category;
      });
    $data['categories'] = $categories;

    $now_time = \Carbon\Carbon::now();

    // All events
    $eventsAll = DB::table('event_contents')
      ->join('events', 'events.id', '=', 'event_contents.event_id')
      ->where([
        ['event_contents.language_id', $language->id],
        ['events.status', 1],
        ['events.end_date_time', '>=', $now_time],
        ['events.is_featured', '=', 'yes'],
      ])
      ->orderBy('events.created_at', 'desc')
      ->get()
      ->map(function ($event) use ($language) {

        return $this->formatEventForApi($event, $language);
      });

    $data['lastest_events'] = $eventsAll->take(5);

    $nextDateSub = DB::table('event_dates')
      ->select(DB::raw('MIN(start_date)'))
      ->whereColumn('event_dates.event_id', 'events.id');

    $data['upcoming_events'] = DB::table('event_contents')
      ->join('events', 'events.id', '=', 'event_contents.event_id')
      ->where('event_contents.language_id', $language->id)
      ->where('events.status', 1)
      ->where(function ($q) use ($now_time, $nextDateSub) {
        $q->where(function ($sub) use ($now_time) {
          $sub->where('events.date_type', 'single')
            ->where('events.start_date', '>=', $now_time);
        })->orWhere(function ($sub) use ($now_time, $nextDateSub) {
          $sub->where('events.date_type', 'multiple')
            ->where($nextDateSub, '>=', $now_time);
        });
      })
      ->select(
        'event_contents.*',
        'events.*',
        DB::raw("
            CASE
              WHEN events.date_type = 'single' THEN events.start_date
              ELSE ({$nextDateSub->toSql()})
            END as next_date
        ")
      )
      ->mergeBindings($nextDateSub)
      ->orderBy('next_date', 'asc')
      ->get()->map(
       function ($event) use ($language) {
           return $this->formatEventForApi($event, $language);
        });

      $data['events']['all'] = $eventsAll;
      // Events per category
      foreach ($categories as $category) {
        $events = DB::table('event_contents')
          ->join('events', 'events.id', '=', 'event_contents.event_id')
          ->where([
            ['event_contents.language_id', $language->id],
            ['event_contents.event_category_id', $category->id],
            ['events.status', 1],
            ['events.end_date_time', '>=', $now_time],
            ['events.is_featured', '=', 'yes'],
          ])
          ->orderBy('events.created_at', 'desc')
          ->get()
          ->map(function ($event) use ($language) {
            return $this->formatEventForApi($event, $language);
          });

        $data['events']['categories'][$category->id] = $events;
      }


    $currencyInfo = $this->getCurrencyInfo();
    $data['base_currency_symbol'] = $currencyInfo->base_currency_symbol;
    $data['base_currency_symbol_position'] = $currencyInfo->base_currency_symbol_position;
    $data['base_currency_text'] = $currencyInfo->base_currency_text;
    $data['base_currency_text_position'] = $currencyInfo->base_currency_text_position;
    $data['base_currency_rate'] = $currencyInfo->base_currency_rate;

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  /* *****************************
     * Format event data for API
     * *****************************/
  private function formatEventForApi($event, $language)
  {
    $event_date = $event->date_type == 'multiple' ? eventLatestDates($event->id) : null;

    $start_date = $event->date_type == 'multiple' ? @$event_date->start_date : $event->start_date;
    $start_time = $event->start_time;

    // Organizer
    if ($event->organizer_id != null) {
      $organizer = Organizer::find($event->organizer_id);
      $organizer_name = $organizer ? $organizer->username : null;
    } else {
      $admin = Admin::first();
      $organizer_name = $admin->username;
    }

    if ($event->event_type == 'online') {
      $ticket = Ticket::where('event_id', $event->id)->orderBy('price', 'asc')->first();
      $start_price = $ticket->price;
    } else {
      $ticket = Ticket::where('event_id', $event->id)->whereNotNull('price')->orderBy('price', 'asc')->first();
      if (!$ticket) {
        $ticket = Ticket::where('event_id', $event->id)->whereNotNull('f_price')->orderBy('price', 'asc')->first();
        $start_price = $ticket->f_price;
      } else {
        $start_price = $ticket->price;
      }
    }

    $customer = Auth::guard('sanctum')->user();
    if (!empty($customer)) {
      $wishlist = Wishlist::where([['event_id', $event->id], ['customer_id', $customer->id]])->first();
    } else {
      $wishlist = null;
    }
    $dates = null;
    if ($event->date_type == 'multiple') {
      $dates = EventDates::where('event_id', $event->id)->get();
    }

    return [
      'id' => $event->id,
      'slug' => $event->slug,
      'title' => $event->title,
      'thumbnail' => asset('assets/admin/img/event/thumbnail/' . $event->thumbnail),
      'date' => $start_date,
      'time' => $start_time,
      'date_type' => $event->date_type,
      'duration' => $event->date_type == 'multiple' ? @$event_date->duration : $event->duration,
      'organizer' => $organizer_name,
      'event_type' => $event->event_type,
      'address' => $event->address,
      'start_price' => $ticket->pricing_type == 'free' ? $ticket->pricing_type : $start_price,
      'wishlist' => !is_null($wishlist) ? 'yes' : 'no',
      'dates' => $dates,
    ];
  }

  /* *****************************
     * basic data
    * *****************************/
  public function getBasic()
  {

    $basicData = DB::table('basic_settings')
      ->select('primary_color', 'mobile_app_logo', 'mobile_favicon', 'base_currency_text', 'base_currency_rate', 'tax', 'commission','shop_tax', 'mobile_primary_colour', 'mobile_breadcrumb_overlay_opacity', 'mobile_breadcrumb_overlay_colour', 'app_google_map_status', 'google_map_api_key', 'google_map_radius')
      ->first();

    $basicData->mobile_app_logo = asset('assets/img/mobile-interface/' . $basicData->mobile_app_logo);
    $basicData->mobile_favicon = asset('assets/img/mobile-interface/' . $basicData->mobile_favicon);

    $data['basic_data'] = $basicData;
    $data['languages'] = Language::all();

    $data['online_gateways'] = DB::table('online_gateways')
      ->where('mobile_status', 1)
      ->whereIn('keyword', [
        'phonepe',
        'mercadopago',
        'myfatoorah',
        'midtrans',
        'authorize.net',
        'toyyibpay',
        'xendit',
        'mollie',
        'paystack',
        'flutterwave',
        'stripe',
        'paypal',
        'razorpay',
        'monnify',
        'now_payments',
        'razorpay'
      ])
      ->select('id', 'name', 'keyword')
      ->get();

    $data['offline_gateways'] = DB::table('offline_gateways')
      ->where('status', 1)
      ->orderBy('serial_number', 'asc')
      ->select('id', 'name', 'short_description', 'instructions', 'has_attachment')
      ->get();

    $stripe = OnlineGateway::where('keyword', 'stripe')->first();
    $stripeInfo = $stripe ? json_decode($stripe->mobile_information, true) : null;
    $data['stripe_public_key'] = $stripeInfo['key'] ?? null;

    $razorpay = OnlineGateway::where('keyword', 'razorpay')->first();
    $data['razorpayInfo'] = $razorpay ? json_decode($razorpay->mobile_information, true) : null;

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  /* *****************************
     * push notifications
    * *****************************/

  public function pushNotificationStore(Request $request)
  {

    $rules = [
      'endpoint' => 'required',
      'keys.p256dh' => 'required',
      'keys.auth' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    $endpoint = $request->endpoint;
    $key = $request->keys['p256dh'];
    $token = $request->keys['auth'];

    $guest = Guest::firstOrCreate([
      'endpoint' => $endpoint
    ]);

    $guest->updatePushSubscription($endpoint, $key, $token);

    return response()->json([
      'status' => 'Success',
      'message' => '',
    ], 200);
  }

}
