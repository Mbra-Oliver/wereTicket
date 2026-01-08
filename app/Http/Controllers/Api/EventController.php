<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\Event\BookingController;
use App\Http\Helpers\GeoSearch;
use App\Jobs\BookingInvoiceJob;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\PageHeading;
use App\Models\Earning;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\Coupon;
use App\Models\Event\EventCategory;
use App\Models\Event\EventCity;
use App\Models\Event\EventContent;
use App\Models\Event\EventCountry;
use App\Models\Event\EventDates;
use App\Models\Event\EventImage;
use App\Models\Event\EventState;
use App\Models\Event\Slot;
use App\Models\Event\SlotImage;
use App\Models\Event\Ticket;
use App\Models\Event\TicketContent;
use App\Models\Event\VariationContent;
use App\Models\Event\Wishlist;
use App\Models\FcmToken;
use App\Models\Language;
use App\Models\Organizer;
use App\Services\BookingServices;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use stdClass;

class EventController extends Controller
{
  private $now_date_time;
  public function __construct()
  {
    $this->now_date_time = Carbon::now();
  }
  /* ***************************
     * Events Page Information
     * ***************************/
  public function index(Request $request)
  {
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $information  = [];
    $information['page_title'] = PageHeading::where('language_id', $language->id)->pluck('event_page_title')->first();

    $categories = EventCategory::where([['language_id', $language->id], ['status', 1]])->orderBy('serial_number', 'asc')
      ->get()
      ->map(function ($category) {
        $category->image = $category->image ? asset('assets/admin/img/event-category/' . $category->image) : null;
        return $category;
      });

    $information['categories'] = $categories;

    //for filter
    $category = $location =  $event_type = $min = $max = $keyword = $date1 = $date2 = $country_id = $state_id = $city_id = null;

    if ($request->country) {
      $country_id = EventCountry::where('language_id', $language->id)
        ->where('slug', $request->country)
        ->value('id');
    }

    if ($request->state) {
      $state_id = EventState::where('language_id', $language->id)
        ->where('slug', $request->state)
        ->value('id');
    }

    if ($request->city) {
      $city_id = EventCity::where('language_id', $language->id)
        ->where('slug', $request->city)
        ->value('id');
    }

    $cscIds = [];
    $cscQuery = EventContent::where('language_id', $language->id);
    if ($country_id) {
      $cscQuery->where('country_id', $country_id);
    }

    if ($state_id) {
      $cscQuery->where('state_id', $state_id);
    }

    if ($city_id) {
      $cscQuery->where('city_id', $city_id);
    }
    $cscIds = $cscQuery->pluck('event_id')->toArray();

    if ($request->filled('category')) {
      $category = $request['category'];
      $category = EventCategory::where([['slug', $category], ['status', 1]])->first();
      $category = $category->id;
    }
    $eventSIds = [];
    if ($request->filled('location')) {
      $location = $request['location'];

      $event_contents = EventContent::where(function ($query) use ($location) {
        return $query->where('address', 'like', '%' . $location . '%')
          ->orWhere('city', 'like', '%' . $location . '%')
          ->orWhere('country', 'like', '%' . $location . '%')
          ->orWhere('state', 'like', '%' . $location . '%');
      })->where('language_id', $language->id)->get();

      foreach ($event_contents as $event_content) {
        if (!in_array($event_content->event_id, $eventSIds)) {
          array_push($eventSIds, $event_content->event_id);
        }
      }
    }

    if ($request->filled('event')) {
      $event_type = $request['event'];
    }
    $eventIds = [];

    if ($request->filled('min') && $request->filled('max')) {
      $min = $request['min'];
      $max = $request['max'];

      $tickets = Ticket::where('tickets.f_price', '>=', $min)->where('tickets.f_price', '<=', $max)->get();

      foreach ($tickets as $ticket) {
        if (!in_array($ticket->event_id, $eventIds)) {
          array_push($eventIds, $ticket->event_id);
        }
      }
    }

    if ($request->filled('search-input')) {
      $keyword = $request['search-input'];
    }
    $eventIds2 = [];
    if ($request->filled('dates')) {

      $dates = $request['dates'];
      $dateArray = explode(' ', $dates);

      $date1 = $dateArray[0];
      $date2 = $dateArray[2];

      $q_events = EventDates::whereDate('start_date', '<=', $date1)->whereDate('end_date', '>=', $date2)->get();
      foreach ($q_events as $evnt) {
        if (!in_array($evnt->event_id, $eventIds2)) {
          array_push($eventIds2, $evnt->event_id);
        }
      }

      $events = Event::whereDate('start_date', '<=', $date1)->whereDate('end_date', '>=', $date2)->get();

      foreach ($events as $event) {
        if (!in_array($event->id, $eventIds2)) {
          array_push($eventIds2, $event->id);
        }
      }
    }

    $information['countries'] = EventCountry::where([
      ['language_id', $language->id],
      ['status', 1]
    ])
      ->orderBy('serial_number', 'asc')
      ->get();

    $information['states'] = EventState::where([
      ['language_id', $language->id],
      ['status', 1]
    ])
      ->orderBy('serial_number', 'asc')
      ->get();

    $information['cities'] = EventCity::where([
      ['language_id', $language->id],
      ['status', 1]
    ])
      ->orderBy('serial_number', 'asc')
      ->get();

    //search by location
    $locationEveIds = [];
    $location = null;
    $lat_long = [];
    $bs = DB::table('basic_settings')->select('google_map_status', 'google_map_radius', 'google_map_api_key')->first();
    $radius = $bs->google_map_status == 1 ? $bs->google_map_radius : 5000;

    if ($request->filled('location')) {
      $location = $request->location;

      if ($bs->google_map_status == 1) {
        $lat_long = GeoSearch::getCoordinates($location, $bs->google_map_api_key);
        // dd($lat_long);
      } else {
        $locationEveIds = EventContent::Where('language_id', $language->id)
          ->where('address', 'like', '%' . $location . '%')
          ->distinct()
          ->pluck('event_id')
          ->toArray();
      }
    }

    $events = EventContent::join('events', 'events.id', 'event_contents.event_id')
      ->where('event_contents.language_id', $language->id)
      ->when($category, function ($query, $category) {
        return $query->where('event_contents.event_category_id', '=', $category);
      })
      ->when($event_type, function ($query, $event_type) {
        return $query->where('events.event_type', '=', $event_type);
      })
      ->when(($min && $max), function ($query) use ($eventIds) {
        return $query->whereIn('events.id', $eventIds);
      })
      ->when(($location && $bs->google_map_status == 0), function ($query) use ($locationEveIds) {
        return $query->whereIn('events.id', $locationEveIds);
      })
      ->when(($request->filled('country') || $request->filled('state') || $request->filled('city')), function ($query) use ($cscIds) {
        return $query->whereIn('events.id', $cscIds);
      })
      ->when(($date1 && $date2), function ($query) use ($eventIds2) {
        return $query->whereIn('events.id', $eventIds2);
      })
      ->when($keyword, function ($query, $keyword) {
        return $query->where('event_contents.title', 'like', '%' . $keyword . '%');
      })
      ->where('events.status', 1)
      ->whereDate('events.end_date_time', '>=', $this->now_date_time)
      ->select('events.*', 'event_contents.title', 'event_contents.city', 'event_contents.state', 'event_contents.country', 'event_contents.address', 'event_contents.zip_code', 'event_contents.slug')
      ->orderBy('events.id', 'desc');
    //condition for geo location search
    if ($bs->google_map_status == 1) {
      if ($location && is_array($lat_long) && isset($lat_long['lat'], $lat_long['lng'])) {
        $events = $events->get()->map(function ($item) use ($lat_long) {
          $item->distance = round(GeoSearch::getDistance(
            $item->latitude,
            $item->longitude,
            $lat_long['lat'],
            $lat_long['lng']
          ));

          return $item;
        })->filter(function ($item) use ($radius) {
          $item =  floatval($item->distance) <= $radius;
          return $item;
        });


        $events = $request->filled('sort') && $request->input('sort') == 'distance-away'
          ? $events->sortByDesc('distance')
          : $events->sortBy('distance');

        $events = $events->values(); // Reset keys
      } elseif ($location && (!isset($lat_long['lat']) || !isset($lat_long['lng']))) {
        $events = $events->get();
      } elseif (!$location && (!isset($lat_long['lat']) || !isset($lat_long['lng']))) {
        $events = $events->get();
      }
    } else {
      $events = $events->get();
    }

    $eventCollection = $events->map(function ($event, $customer) {

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
        'title' => $event->title,
        'thumbnail' => asset('assets/admin/img/event/thumbnail/' . $event->thumbnail),
        'address' => $event->address,
        'date' => $start_date,
        'time' => $start_time,
        'date_type' => $event->date_type,
        'duration' => $event->date_type == 'multiple' ? @$event_date->duration : $event->duration,
        'organizer' => $organizer_name,
        'event_type' => $event->event_type,
        'start_price' => $ticket->pricing_type == 'free' ? $ticket->pricing_type : $start_price,
        'wishlist' => !is_null($wishlist) ? 'yes' : 'no',
        'dates' => $dates,
      ];
    });

    $information['max'] = $max;
    $information['min'] = $min;
    $information['events'] = $eventCollection;

    $currencyInfo = $this->getCurrencyInfo();

    $information['base_currency_symbol'] = $currencyInfo->base_currency_symbol;
    $information['base_currency_symbol_position'] = $currencyInfo->base_currency_symbol_position;
    $information['base_currency_text'] = $currencyInfo->base_currency_text;
    $information['base_currency_text_position'] = $currencyInfo->base_currency_text_position;
    $information['base_currency_rate'] = $currencyInfo->base_currency_rate;


    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  /* *****************************
     * Event Details Page
     * *****************************/
  public function details(Request $request)
  {
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();

    $event_id = $request->event_id;

    $information['page_title'] = PageHeading::where('language_id', $language->id)->pluck('event_details_page_title')->first();

    // Base query for event content
    $content = EventContent::join('events', 'events.id', 'event_contents.event_id')
      ->join('event_categories', 'event_categories.id', '=', 'event_contents.event_category_id')
      ->where('event_contents.language_id', $language->id)
      ->where('events.id', $event_id)->select(
        'events.*',
        'event_contents.title',
        'event_contents.description',
        'event_contents.event_category_id',
        'event_categories.name',
        'event_contents.city',
        'event_contents.state',
        'event_contents.country',
        'event_contents.address',
        'event_contents.zip_code',
        'event_contents.refund_policy'
      )
      ->first();

    if (empty($content)) {
      return response()->json([
        'success' => false,
        'message' => 'The event is not found'
      ]);
    }

    $dates = null;
    if ($content->date_type == 'multiple') {
      $dates = EventDates::where('event_id', $content->id)->get();
    }
    $content->dates =  $dates;

    $information['content'] = $content;
    if (!is_null(@$content->organizer_id)) {
      $organizer = Organizer::join('organizer_infos', 'organizers.id', '=', 'organizer_infos.organizer_id')
        ->where('organizer_id', $content->organizer_id)
        ->select('name', 'address', 'photo')
        ->first();
      $image_url = !is_null($organizer->photo) ? asset('assets/admin/img/organizer-photo/' . $organizer->photo) : asset('assets/front/images/user.png');
      $organizer->photo = $image_url;
      $information['organizer'] = $organizer;
    } else {
      $admin = Admin::first();
      $admin->image = !is_null($admin->image) ? asset('assets/admin/img/admins/' . $admin->image) : asset('assets/admin/img/blank_user.jpg');
      $information['admin'] = $admin;
    }

    // Check if tickets exist and modify query accordingly
    $tickets = Ticket::where('tickets.event_id', $event_id)
      ->get();


    $tickets->map(function ($ticket) use ($event_id, $content, $language) {
      if ($ticket->event_type == 'online') {
        $ticket->title = $content->title ?? "";
      } else {
        $ticketContent = TicketContent::where('ticket_id', $ticket->id)->where('language_id', $language->id)->first();
        if (!is_null($ticketContent)) {
          $ticket->title = $ticketContent->title ?? "";
        }
      }
      if ($ticket->event_type == 'online' && $ticket->pricing_type == 'normal') {
        if ($ticket->early_bird_discount == 'enable') {
          $discount_date = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
          if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast()) {
            $discountable_price_show = true;
            $calculate_price = $ticket->price - $ticket->early_bird_discount_amount;
          } elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast()) {
            $c_price = ($ticket->price * $ticket->early_bird_discount_amount) / 100;
            $calculate_price = $ticket->price - $c_price;
            $discountable_price_show = true;
          } else {
            $discountable_price_show = false;
            $calculate_price = $ticket->price;
          }
        } else {
          $discountable_price_show = false;
          $calculate_price = $ticket->price;
        }

        if ($ticket->ticket_available_type == 'limited') {
          $stock = $ticket->ticket_available;
        } else {
          $stock = 99999999;
        }

        $ticket->ticket_stock = $stock;
        $ticket->slot_seat_active = null;
        $ticket->slot_unique_id = null;
        $ticket->seat_is_available = null;
        $ticket->discountable_price_show = $discountable_price_show;
        $ticket->total_price = $ticket->price;
        $ticket->payable_price = $calculate_price;
      } elseif ($ticket->event_type == 'online' && $ticket->pricing_type == 'free') {

        if ($ticket->ticket_available_type == 'limited') {
          $stock = $ticket->ticket_available;
        } else {
          $stock = 99999999;
        }
        $ticket->ticket_stock = $stock;
        $ticket->slot_seat_active = null;
        $ticket->slot_unique_id = null;
        $ticket->seat_is_available = null;
        $ticket->discountable_price_show = false;
        $ticket->total_price = 0.00;
        $ticket->payable_price = 0.00;
      } elseif ($ticket->event_type == 'venue') {
        if ($ticket->pricing_type == 'normal') {
          //normal ticket
          if ($ticket->normal_ticket_slot_enable == 1) {
            $slotBookService = new BookingServices();
            $slotItem = $slotBookService->showSlot($event_id, $ticket->id, $ticket->normal_ticket_slot_unique_id);
            $slotPrice = $slotItem['price'];
            $calculate_price = $slotPrice;
            $discountable_price_show = false;
            if ($ticket->early_bird_discount == 'enable') {
              $discount_date = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast()) {
                $calculate_price = $slotPrice - $ticket->early_bird_discount_amount;
                $discountable_price_show = true;
              } elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast()) {
                $c_price = ($slotPrice * $ticket->early_bird_discount_amount) / 100;
                $calculate_price = $slotPrice - $c_price;
                $discountable_price_show = true;
              } else {
                $calculate_price = $slotPrice;
              }
            }
            if ($slotItem['available_seat'] == true) {
              $seat_is_available = 'available';
            } else {
              $is_slot = Slot::where(
                [
                  'slot_unique_id' => $ticket->normal_ticket_slot_unique_id,
                  'event_id' => $event_id,
                  'ticket_id' => $ticket->id,
                ]
              )->first();

              if (!empty($is_slot)) {
                $seat_is_available = 'booked';
              } else {
                $seat_is_available = 'no_seat_found';
              }
            }

            $ticket->ticket_stock = null;
            $ticket->slot_seat_active = $ticket->normal_ticket_slot_enable;
            $ticket->slot_unique_id = $ticket->normal_ticket_slot_unique_id;
            $ticket->seat_is_available = $seat_is_available;
            $ticket->discountable_price_show = $discountable_price_show;
            $ticket->total_price = $slotPrice;
            $ticket->payable_price = $calculate_price;
          } else {
            $calculate_price = $ticket->price;
            $discountable_price_show = false;
            if ($ticket->early_bird_discount == 'enable') {
              $discount_date = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast()) {
                $calculate_price = $ticket->price - $ticket->early_bird_discount_amount;
                $discountable_price_show = true;
              } elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast()) {
                $c_price = ($ticket->price * $ticket->early_bird_discount_amount) / 100;
                $calculate_price = $ticket->price - $c_price;
                $discountable_price_show = true;
              } else {
                $calculate_price = $ticket->price;
              }
            }

            if ($ticket->ticket_available_type == 'limited') {
              $stock = $ticket->ticket_available;
            } else {
              $stock = 9999999;
            }

            $ticket->ticket_stock = $stock;
            $ticket->slot_seat_active = $ticket->normal_ticket_slot_enable;
            $ticket->slot_unique_id = $ticket->normal_ticket_slot_unique_id;
            $ticket->seat_is_available = null;
            $ticket->discountable_price_show = $discountable_price_show;
            $ticket->total_price = $ticket->price;
            $ticket->payable_price = $calculate_price;
          }
          $ticket->variations = collect();
        } elseif ($ticket->pricing_type == 'variation') {
          //varitations
          if (!is_null($ticket->variations)) {
            $variations = json_decode($ticket->variations, true);
            $slotBookService = new BookingServices();
            $updatedVariations = [];
            foreach ($variations as $key => $item) {
              $item = new Fluent($item);
              if ($item->slot_enable == 1) {
                $slotItem = $slotBookService->showSlot($event_id, $ticket->id, $item->slot_unique_id);
                $slotPrice = $slotItem['price'];
                if ($slotItem['available_seat'] == true) {
                  $seat_is_available = 'available';
                  if ($ticket->early_bird_discount == 'enable') {
                    $discount_date = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
                    if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast()) {
                      $calculate_price = $slotPrice - $ticket->early_bird_discount_amount;
                      $discountable_price_show = true;
                    } elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast()) {
                      $c_price = ($slotPrice * $ticket->early_bird_discount_amount) / 100;
                      $calculate_price = $slotPrice - $c_price;
                      $discountable_price_show = true;
                    } else {
                      $discountable_price_show = false;
                      $calculate_price = $slotPrice;
                    }
                  } else {
                    $discountable_price_show = false;
                    $calculate_price = $slotPrice;
                  }
                } else {
                  $discountable_price_show = false;
                  $calculate_price = $slotItem['price'];
                  $is_slot = Slot::where([
                    'slot_unique_id' => $item->slot_unique_id,
                    'event_id' => $event_id,
                    'ticket_id' => $ticket->id,
                  ])->first();
                  if (!empty($is_slot)) {
                    $seat_is_available = 'booked';
                  } else {
                    $seat_is_available = 'no_seat_found';
                  }
                }

                $item->ticket_stock = null;
                $item->purchase_status = null;
                $item->purchase_qty = null;
                $item->slot_seat_active = $item->slot_enable;
                $item->slot_unique_id = $item->slot_unique_id;
                $item->seat_is_available = $seat_is_available;
                $item->discountable_price_show = $discountable_price_show;
                $item->total_price = $slotPrice;
                $item->payable_price = $calculate_price;
              } else {
                $discount_date = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
                if ($ticket->early_bird_discount == 'enable') {
                  if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast()) {
                    $calculate_price = $item->price - $ticket->early_bird_discount_amount;
                    $discountable_price_show = true;
                  } elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast()) {
                    $c_price = ($item->price * $ticket->early_bird_discount_amount) / 100;
                    $calculate_price = $item->price - $c_price;
                    $discountable_price_show = true;
                  } else {
                    $calculate_price = $item->price;
                    $discountable_price_show = false;
                  }

                  $de_varition_names = VariationContent::where(
                    [['ticket_id', $ticket->id]],
                  )->get();

                  if (Auth::guard('customer')->user()) {
                    if (count($de_varition_names) > 0) {
                      $purchase = isTicketPurchaseVenue(
                        $ticket->event_id,
                        $item->v_max_ticket_buy,
                        $ticket->id,
                        $de_varition_names[$key]['name'],
                      );
                    }
                  } else {
                    $purchase = ['status' => 'false', 'p_qty' => 0];
                  }

                  if ($item->ticket_available_type == 'limited') {
                    $stock = $item->ticket_available;
                  } else {
                    $stock = 99999999;
                  }

                  $item->ticket_stock = $stock;
                  $item->purchase_status = $purchase['status'];
                  $item->purchase_qty = $purchase['p_qty'];
                  $item->slot_seat_active = $item->slot_enable;
                  $item->slot_unique_id = $item->slot_unique_id;
                  $item->seat_is_available = null;
                  $item->discountable_price_show = $discountable_price_show;
                  $item->total_price = $item->price;
                  $item->payable_price = $calculate_price;
                } else {

                  $de_varition_names = VariationContent::where(
                    [['ticket_id', $ticket->id]],
                  )->get();

                  if (Auth::guard('customer')->user()) {
                    if (count($de_varition_names) > 0) {
                      $purchase = isTicketPurchaseVenue(
                        $ticket->event_id,
                        $item->v_max_ticket_buy,
                        $ticket->id,
                        $de_varition_names[$key]['name'],
                      );
                    }
                  } else {
                    $purchase = ['status' => 'false', 'p_qty' => 0];
                  }

                  if ($item->ticket_available_type == 'limited') {
                    $stock = $item->ticket_available;
                  } else {
                    $stock = 99999999;
                  }

                  $item->ticket_stock = $stock;
                  $item->purchase_status = $purchase['status'];
                  $item->purchase_qty = $purchase['p_qty'];
                  $item->slot_seat_active = $item->slot_enable;
                  $item->slot_unique_id = $item->slot_unique_id;
                  $item->seat_is_available = null;
                  $item->discountable_price_show = false;
                  $item->total_price = $item->price;
                  $item->payable_price = $item->price;
                }
              }

              $updatedVariations[] = $item;
            }
            $ticket->variations = $updatedVariations;
          } else {
            $ticket->variations = collect();
          }

          $ticket->ticket_stock = null;
          $ticket->slot_seat_active = null;
          $ticket->slot_unique_id = null;
          $ticket->seat_is_available = null;
          $ticket->discountable_price_show = 0.00;
          $ticket->total_price = 0.00;
          $ticket->payable_price = 0.00;
        } elseif ($ticket->pricing_type == 'free') {
          //free ticket
          if ($ticket->free_tickete_slot_enable == 1) {
            $slotBookService = new BookingServices();
            $slotItem = $slotBookService->showSlot($event_id, $ticket->id, $ticket->free_tickete_slot_unique_id);
            $slotPrice = $slotItem['price'];
            $calculate_price = 0.00;
            $discountable_price_show = false;

            if ($slotItem['available_seat'] == true) {
              $seat_is_available = 'available';
            } else {
              $is_slot = Slot::where(
                [
                  'slot_unique_id' => $ticket->free_tickete_slot_unique_id,
                  'event_id' => $event_id,
                  'ticket_id' => $ticket->id,
                ]
              )->first();
              if (!empty($is_slot)) {
                $seat_is_available = 'booked';
              } else {
                $seat_is_available = 'no_seat_found';
              }
            }

            $ticket->ticket_stock = null;
            $ticket->slot_seat_active = $ticket->free_tickete_slot_enable;
            $ticket->slot_unique_id = $ticket->free_tickete_slot_unique_id;
            $ticket->seat_is_available = $seat_is_available;
            $ticket->discountable_price_show = false;
            $ticket->total_price = 0.00;
            $ticket->payable_price = 0.00;
          } else {

            if ($ticket->ticket_available_type == 'limited') {
              $stock = $ticket->ticket_available;
            } else {
              $stock = 9999999;
            }

            $ticket->ticket_stock = $stock;
            $ticket->slot_seat_active = $ticket->free_tickete_slot_enable;
            $ticket->slot_unique_id = $ticket->free_tickete_slot_unique_id;
            $ticket->seat_is_available = null;
            $ticket->discountable_price_show = false;
            $ticket->total_price = 0.00;
            $ticket->payable_price = 0.00;
          }
          $ticket->variations = collect();
        }
      }
      return $ticket;
    });

    $information['tickets'] = $tickets;
    $information['images'] = EventImage::where('event_id', $event_id)
      ->get()
      ->map(function ($data) {
        $data->image = asset('assets/admin/img/event-gallery/' . $data->image);
        return $data;
      });

    // Related events
    if ($content) {
      $related_events_data = EventContent::join('events', 'events.id', 'event_contents.event_id')
        ->where('event_contents.language_id', $language->id)
        ->where('event_contents.event_category_id', $content->event_category_id)
        ->where('events.id', '!=', $event_id)
        ->whereDate('events.end_date_time', '>=', $this->now_date_time)
        ->select('events.*', 'event_contents.title', 'event_contents.description', 'event_contents.slug', 'event_contents.city', 'event_contents.country')
        ->orderBy('events.id', 'desc')
        ->get();

      $related_events = $related_events_data->map(function ($event, $customer) {
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
          'title' => $event->title,
          'thumbnail' => asset('assets/admin/img/event/thumbnail/' . $event->thumbnail),
          'address' => $event->address,
          'date' => $start_date,
          'time' => $start_time,
          'date_type' => $event->date_type,
          'duration' => $event->date_type == 'multiple' ? @$event_date->duration : $event->duration,
          'organizer' => $organizer_name,
          'event_type' => $event->event_type,
          'start_price' => $ticket->pricing_type == 'free' ? $ticket->pricing_type : $start_price,
          'wishlist' => !is_null($wishlist) ? 'yes' : 'no',
          'dates' => $dates,
        ];
      });
    } else {
      $related_events = [];
    }

    $information['related_events'] = $related_events;

    $currencyInfo = $this->getCurrencyInfo();
    $information['base_currency_symbol'] = $currencyInfo->base_currency_symbol;
    $information['base_currency_symbol_position'] = $currencyInfo->base_currency_symbol_position;
    $information['base_currency_text'] = $currencyInfo->base_currency_text;
    $information['base_currency_text_position'] = $currencyInfo->base_currency_text_position;
    $information['base_currency_rate'] = $currencyInfo->base_currency_rate;
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  /* ******************************
     * Store event booking
     * ****************************/
  public function store_booking(Request $request)
  {
    $rules = [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required',
      'phone' => 'required',
      'country' => 'required',
      'city' => 'nullable',
      'state' => 'nullable',
      'zip_code' => 'nullable',
      'address' => 'required',
      'event_id' => 'required',
      'gateway' => 'required',
      'gatewayType' => 'required',
      'quantity' => 'required',
      'selTickets' => 'nullable',
      'event_date' => 'required',
      'total' => 'required',
      'discount' => 'required',
      'tax' => 'required',
      'total_early_bird_dicount' => 'required',
      'customer_id' => 'nullable',
      'paymentStatus' => 'nullable',
      'fcm_token' => 'nullable',
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'validation_errors' => $validator->getMessageBag()->toArray()
      ]);
    }

    //offline gateway
    if ($request['gatewayType'] == 'offline' && $request->hasFile('attachment')) {
      $filename = time() . '.' . $request->file('attachment')->getClientOriginalExtension();
      $request->file('attachment')->move(public_path('assets/admin/file/attachments/'), $filename);
    }

    $currencyInfo = $this->getCurrencyInfo();

    $total = $request->total;
    $discount = $request->discount;
    $total_early_bird_dicount = $request->total_early_bird_dicount;
    $tax_amount = $request->tax;

    $basicSetting = Basic::select('commission')->first();
    $commission_amount = ($total * $basicSetting->commission) / 100;

    $paymentStatus = $request->paymentStatus;
    if (empty($paymentStatus)) {
      $paymentStatus = $request->gatewayType == 'online' ? 'completed' : 'pending';
    }

    $customerId = $request->customer_id;
    if (empty($customerId)) {
      $customerId = 'guest';
    }

    $arrData = array(
      'event_id' => $request->event_id,
      'currencyText' =>  $currencyInfo->base_currency_text,
      'currencyTextPosition' => $currencyInfo->base_currency_text_position,
      'currencySymbol' => $currencyInfo->base_currency_symbol,
      'currencySymbolPosition' => $currencyInfo->base_currency_symbol_position,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'email' => $request->email,
      'phone' => $request->phone,
      'country' => $request->country,
      'state' => $request->state,
      'city' => $request->city,
      'zip_code' => $request->city,
      'address' => $request->address,
      'paymentMethod' => $request->gateway,
      'gatewayType' => Str::lower($request->gatewayType),
      'paymentStatus' => $paymentStatus,
      'event_date' => $request->event_date,
      'selTickets' => $request->selTickets,
      'attachmentFile' => isset($filename) ? $filename : null,
      'fcm_token' => $request->fcm_token,
      'price' => $total,
      'commission' => $commission_amount,
      'quantity' => $request->quantity,
      'discount' => $discount,
      'total_early_bird_dicount' => $total_early_bird_dicount,
      'tax' => $tax_amount,
      'customer_id' => $customerId
    );

    $bookingInfo = $this->storeData($arrData);

    if (!is_null($bookingInfo) && $request->gatewayType == 'online' && $paymentStatus == 'completed') {
      $ticket = DB::table('basic_settings')->select('how_ticket_will_be_send')->first();
      if ($ticket->how_ticket_will_be_send == 'instant') {
        // generate an invoice in pdf format
        $booking_controller = new BookingController();
        $invoice = $booking_controller->generateInvoice($bookingInfo, $bookingInfo->event_id);
        //unlink qr code
        if (!is_null($bookingInfo->variation)) {
          //generate qr code for without wise ticket
          $variations = json_decode($bookingInfo->variation, true);
          foreach ($variations as $variation) {
            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '__' . $variation['unique_id'] . '.svg');
          }
        } else {
          //generate qr code for without wise ticket
          for ($i = 1; $i <= $bookingInfo->quantity; $i++) {
            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '__' . $i .  '.svg');
          }
        }
        // then, update the invoice field info in database
        $bookingInfo->invoice = $invoice;
        $bookingInfo->save();

        // send a mail to the customer with the invoice
        $booking_controller->sendMail($bookingInfo);
      } else {
        BookingInvoiceJob::dispatch($bookingInfo->id)->delay(now()->addSeconds(10));
      }

      //earning revenue
      $this->earning_revenue($bookingInfo);
      //storeTransaction
      $bookingInfo['paymentStatus'] = 1;
      $bookingInfo['transcation_type'] = 1;
      storeTranscation($bookingInfo);

      //store amount to organizer
      if (!empty($bookingInfo->organizer_id)) {
        $organizerData['organizer_id'] = $bookingInfo->organizer_id;
        $organizerData['price'] = $arrData['price'];
        $organizerData['tax'] = $bookingInfo->tax;
        $organizerData['commission'] = $bookingInfo->commission;
        storeOrganizer($organizerData);
      }
    }

    //send notification
    $firebase_admin_json = DB::table('basic_settings')
      ->where('uniqid', 12345)
      ->value('firebase_admin_json');

    if (!empty($bookingInfo->fcm_token) && !is_null($firebase_admin_json)) {
      $title = __('Event Booking Complete');
      $subtitle = "Your current payment status " . $paymentStatus;
      FcmToken::create([
        'token' => $bookingInfo->fcm_token,
        'user_id' => $bookingInfo->customer_id != 'guest' ? $bookingInfo->customer_id : null,
        'platform' => 'web',
        'message_title' => $title,
        'message_description' => $subtitle,
        'booking_id' => $bookingInfo->id,
      ]);
      FirebaseService::send($bookingInfo->fcm_token, $bookingInfo->id, $title, $subtitle);

    }

    if(!empty($bookingInfo->invoice)){
      $bookingInfo->invoice = asset('assets/admin/file/invoices/' . $bookingInfo->invoice);
    }

    return response()->json([
      'status' => true,
      'message' => 'Booking created successfully',
      'booking_info' => $bookingInfo
    ]);

  }

  private function storeData($info)
  {

    try {
      $event = Event::find($info['event_id']);
      if ($event) {
        if ($event->organizer_id) {
          $organizer_id = $event->organizer_id;
        } else {
          $organizer_id = null;
        }
      }
      $variations = $info['selTickets'];

      if (!empty($variations)) {
        foreach ($variations as $variation) {
          $ticket = Ticket::where('id', $variation['ticket_id'])->first();
          if ($ticket->pricing_type == 'normal' && $ticket->ticket_available_type == 'limited') {
            if ($ticket->ticket_available - $variation['qty'] >= 0) {
              $ticket->ticket_available = $ticket->ticket_available - $variation['qty'];
              $ticket->save();
            }
          } elseif ($ticket->pricing_type == 'variation') {
            $ticket_variations =  json_decode($ticket->variations, true);
            $update_variation = [];
            foreach ($ticket_variations as $ticket_variation) {
              if ($ticket_variation['name']  == $variation['name']) {

                if ($ticket_variation['ticket_available_type'] == 'limited') {
                  $ticket_available = intval($ticket_variation['ticket_available']) - intval($variation['qty']);
                } else {
                  $ticket_available = $ticket_variation['ticket_available'];
                }

                $update_variation[] = [
                  'name' => $ticket_variation['name'],
                  'price' => round($ticket_variation['price'], 2),
                  'ticket_available_type' => $ticket_variation['ticket_available_type'],
                  'ticket_available' => $ticket_available,
                  'max_ticket_buy_type' => $ticket_variation['max_ticket_buy_type'],
                  'v_max_ticket_buy' => $ticket_variation['v_max_ticket_buy'],
                  'slot_enable' => $ticket_variation['slot_enable'] ?? 0,
                  'slot_unique_id' =>  $ticket_variation['slot_unique_id'] ?? rand(000000, 99999),
                  'slot_seat_min_price' =>  $ticket_variation['slot_seat_min_price'] ?? 0.00,
                ];
              } else {
                $update_variation[] = [
                  'name' => $ticket_variation['name'],
                  'price' => round($ticket_variation['price'], 2),
                  'ticket_available_type' => $ticket_variation['ticket_available_type'],
                  'ticket_available' => $ticket_variation['ticket_available'],
                  'max_ticket_buy_type' => $ticket_variation['max_ticket_buy_type'],
                  'v_max_ticket_buy' => $ticket_variation['v_max_ticket_buy'],
                  'slot_enable' => $ticket_variation['slot_enable'] ?? 0,
                  'slot_unique_id' =>  $ticket_variation['slot_unique_id'] ?? rand(000000, 99999),
                  'slot_seat_min_price' =>  $ticket_variation['slot_seat_min_price'] ?? 0.00,
                ];
              }
            }
            $ticket->variations = json_encode($update_variation, true);


            $ticket->save();
          } elseif ($ticket->pricing_type == 'free' && $ticket->ticket_available_type == 'limited') {
            if ($ticket->ticket_available - $variation['qty'] >= 0) {
              $ticket->ticket_available = $ticket->ticket_available - $variation['qty'];
              $ticket->save();
            }
          }
        }

        /*****************************************
         * update selltickets for each ticket
         ******************************************/

        $variations = $info['selTickets'];
        $c_variations = [];
        foreach ($variations as $variation) {
          for ($i = 1; $i <= $variation['qty']; $i++) {
            $c_variations[] = [
              'ticket_id' => $variation['ticket_id'],
              'early_bird_dicount' => $variation['early_bird_dicount'],
              'name' => $variation['name'],
              'qty' => 1,
              'price' => $variation['price'],
              'scan_status' => 0,
              'unique_id' => uniqid(),
            ];
            $lastIndex = array_key_last($c_variations);
            if (array_key_exists('seat_id',  $variation)) {
              $c_variations[$lastIndex]['seat_id'] = $variation['seat_id'];
            }
            if (array_key_exists('seat_name',  $variation)) {
              $c_variations[$lastIndex]['seat_name'] = $variation['seat_name'];
            }
            if (array_key_exists('slot_id',  $variation)) {
              $c_variations[$lastIndex]['slot_id'] = $variation['slot_id'];
            }
            if (array_key_exists('slot_name',  $variation)) {
              $c_variations[$lastIndex]['slot_name'] = $variation['slot_name'];
            }
            if (array_key_exists('slot_unique_id',  $variation)) {
              $c_variations[$lastIndex]['slot_unique_id'] = $variation['slot_unique_id'];
            }
          }
        }
        $variations = json_encode($c_variations, true);
      } else {
        $ticket = $event->ticket()->first();
        $ticket->ticket_available = $ticket->ticket_available - (int)$info['quantity'];
        $ticket->save();
      }

      $basic  = Basic::where('uniqid', 12345)->select('tax', 'commission')->first();

      $booking = Booking::create([
        'customer_id' => array_key_exists('customer_id', $info) ? $info['customer_id'] : null,
        'booking_id' => uniqid(),
        'fname' => $info['fname'],
        'lname' => $info['lname'],
        'email' => $info['email'],
        'phone' => $info['phone'],
        'country' => $info['country'],
        'state' => $info['state'],
        'city' => $info['city'],
        'zip_code' => $info['zip_code'],
        'address' => $info['address'],
        'event_id' => $info['event_id'],
        'organizer_id' => $organizer_id,
        'variation' => $variations,
        'price' => round($info['price'], 2),
        'tax' => round($info['tax'], 2),
        'commission' => round($info['commission'], 2),
        'tax_percentage' => $basic->tax,
        'commission_percentage' => $basic->commission,
        'quantity' => $info['quantity'],
        'discount' => round($info['discount'], 2),
        'early_bird_discount' => round($info['total_early_bird_dicount'], 2),
        'currencyText' => $info['currencyText'],
        'currencyTextPosition' => $info['currencyTextPosition'],
        'currencySymbol' => $info['currencySymbol'],
        'currencySymbolPosition' => $info['currencySymbolPosition'],
        'paymentMethod' => $info['paymentMethod'],
        'gatewayType' => $info['gatewayType'],
        'paymentStatus' => $info['paymentStatus'],
        'invoice' => array_key_exists('invoice', $info) ? $info['invoice'] : null,
        'attachmentFile' => array_key_exists('attachmentFile', $info) ? $info['attachmentFile'] : null,
        'event_date' => $info['event_date'],
        'conversation_id' => array_key_exists('conversation_id', $info) ? $info['conversation_id'] : null,
        'fcm_token' => array_key_exists('fcm_token', $info) ? $info['fcm_token'] : null,
      ]);

      return $booking;
    } catch (\Exception $e) {
      return response()->json([
        'status' => false,
        'message' => $e->getMessage()
      ]);
    }
  }
  public function categories(Request $request)
  {
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();
    $information['categories'] = EventCategory::where('language_id', $language->id)
      ->where('status', 1)
      ->orderBy('serial_number', 'asc')
      ->get()->map(function ($category) {
        $category->image = asset('assets/admin/img/event-category/' . $category->image);
        return $category;
      });

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  public function slotMapping(Request $request)
  {

    $event_id = $request->event_id;
    $ticket_id = $request->ticket_id;
    $slot_unique_id = $request->slot_unique_id;


    if (empty($event_id) || empty($ticket_id) || empty($slot_unique_id)) {
      return response()->json([
        'success' => false,
        'message' => 'incorrect url'
      ], 401);
    }


    $ticket_id = $ticket_id;
    $slot_unique_id = $slot_unique_id;
    $event_id = $event_id;

    $ticket = Ticket::find($ticket_id);
    $seatMappingImage = SlotImage::where([
      'event_id' => $event_id,
      'ticket_id' => $ticket_id,
      'slot_unique_id' => $slot_unique_id,
    ])->first();

    if (!$seatMappingImage) {
      return response()->json([
        'success' => false,
        'message' => "No Seat Available",
        'slots' => [],
      ]);
    }
    $bookedTicketData =  app(\App\Services\BookingServices::class)->getBookingDeactiveData($event_id);

    $data['slot_image'] = !empty($seatMappingImage->image) ? asset('assets/admin/img/map-image/' . $seatMappingImage->image) : "";
    $data['pricing_type'] = $ticket->pricing_type;

    $slots = Slot::where([
      'event_id' => $event_id,
      'ticket_id' => $ticket_id,
      'slot_unique_id' => $slot_unique_id,
    ])->with('seats')->get();

    $allSlots = $slots->map(function ($slot) use ($bookedTicketData, $ticket) {

      $obj = new stdClass();
      $obj->id = $slot->id;
      $obj->event_id   = $slot->event_id;
      $obj->ticket_id  = $slot->ticket_id;
      $obj->slot_name = $slot->name;
      $obj->slot_type = $slot->type;
      $obj->slot_unique_id  = $slot->slot_unique_id;
      $obj->slot_pos_x  = $slot->pos_x;
      $obj->slot_pos_y  = $slot->pos_y;
      $obj->slot_width  = $slot->width;
      $obj->slot_height  = $slot->height;
      $obj->slot_round  = $slot->round;
      $obj->slot_rotate  = $slot->rotate;
      $obj->slot_background_color  = $slot->background_color;
      $obj->slot_border_color  = $slot->border_color;
      $obj->slot_font_size  = $slot->font_size;


      $thisSlotSeats = $slot->filtered_seats->each(function ($item) use ($ticket, $slot, $bookedTicketData) {

        if ($ticket->early_bird_discount == 'enable') {
          $discount_date = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
          if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast()) {
            $calculate_price = $item->price - $ticket->early_bird_discount_amount;
          } elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast()) {
            $c_price = ($item->price * $ticket->early_bird_discount_amount) / 100;
            $calculate_price = $item->price - $c_price;
          } else {
            $calculate_price =  $item->price;
          }
        } else {
          $calculate_price = $item->price;
        }
        $item->payable_price = $calculate_price;
        $item->seat_type = $slot->type;
        //when seat is deactive
        $check_booked =  $item->is_deactive;
        //when check is_booked
        if ($check_booked == 0) {
          $check_booked =  in_array($item->id, $bookedTicketData['seat_ids']) ? 1 : 0;
        }
        $item->is_booked = $check_booked;
        return $item;
      });

      $obj->seats = $thisSlotSeats;

      $check_booked =  $slot->is_deactive;
      if ($check_booked == 0) {
        if ($slot->type == 2) {
          $check_booked = in_array($slot->id, $bookedTicketData['slot_ids']) ? 1 : 0;
        } else {
          $activeSeatCount = $slot->seats->where('is_deactive', 0)->count();
          $bookedSeatCount = $slot->seats->where('is_booked', 1)->count();
          $check_booked =  $activeSeatCount <= $bookedSeatCount ? 1 : 0;
        }
      }
      $obj->is_booked = $check_booked;
      return $obj;
    });


    $data['slots'] = $allSlots;

    return response()->json([
      'success' => true,
      'data' => $data,
    ]);
  }

  public function checkout2($data)
  {
    $data = new Fluent($data);
    $basic = Basic::select('event_guest_checkout_status')->first();
    $event_guest_checkout_status = $basic->event_guest_checkout_status;
    if ($event_guest_checkout_status != 1) {
      if (Auth::guard('sanctum')->check() == false) {
        $information['success'] = false;
        $information['message'] = "login Required";
        return $information;
      }
    }

    $selected_seats = !empty($data->seatData) ? $data->seatData : [];

    $selected_slot_seat = collect($selected_seats)
      ->groupBy('slot_id')
      ->map(function ($group) {
        $first = $group->first();
        return [
          'slot_id' => $first['slot_id'],
          'slot_name' => $first['slot_name'],
          'event_id' => $first['event_id'],
          'ticket_id' => $first['ticket_id'],
          'slot_unique_id' => $first['slot_unique_id'],
          'slot_type' => $first['s_type'],
          'seats' => collect($group)->map(function ($seat) {
            return [
              'seat_id' => $seat['id'],
              'seat_name' => $seat['name'],
              'discount' => $seat['discount'],
              'price' => $seat['price'],
              'payable_price' => $seat['payable_price'],
            ];
          })->values()->toArray(),
        ];
      })
      ->map(function ($slot) {
        // count and sum for each slot
        $slot['seat_count'] = count($slot['seats']);
        $slot['seats_price'] = collect($slot['seats'])->sum('payable_price');
        return $slot;
      })
      ->values()
      ->toArray();

    $select = false;
    $event_type = Event::where('id', $data->event_id)->select('event_type')->first();

    if ($event_type->event_type == 'venue') {
      foreach ($data->quantity as $qty) {
        if ($qty > 0) {
          $select = true;
          break;
        }
        continue;
      }

      //slot validation for variations wise seats
      if (count($selected_slot_seat) > 0) {
        $select = true;
      }
    } else {
      if ($data->pricing_type == 'free') {
        $select = true;
        //free ticket validation
        if (count($selected_slot_seat) > 0) {
          $select = true;
        }
      } elseif ($data->pricing_type == 'normal') {
        if ($data->quantity == 0) {
          $select = false;
        } else {
          $select = true;
        }

        //when selected slot & seat pricing type normal
        if (count($selected_slot_seat) > 0) {
          $select = true;
        }
      } else {
        foreach ($data->quantity as $qty) {
          if ($qty > 0) {
            $select = true;
            break;
          }
          continue;
        }
      }
    }


    if ($select == false) {
      $information['success'] = false;
      $information['message'] = "Please Select at least one ticket";
      return $information;
    }

    $information = [];
    $information['selTickets'] = '';
    $event = Event::where('id', $data->event_id)->select('event_type', 'id')->first();

    $check = false;


    if ($event->event_type == 'online') {
      //**************** stock check start *************** */
      $stock = StockCheck($data->event_id, $data->quantity);
      if ($stock == 'error') {
        $check = true;
      }

      //*************** stock check end **************** */

      if ($data->pricing_type == 'normal') {
        $price = Ticket::where('event_id', $data->event_id)->select('price', 'early_bird_discount', 'early_bird_discount_amount', 'early_bird_discount_type', 'early_bird_discount_date', 'early_bird_discount_time', 'ticket_available', 'ticket_available_type', 'max_ticket_buy_type', 'max_buy_ticket')->first();
        $information['quantity'] = $data->quantity;
        $total = $data->quantity * $price->price;

        //check guest checkout status enable or not
        if ($event_guest_checkout_status != 1) {
          //check max buy by customer
          $max_buy = isTicketPurchaseOnline($data->event_id, $price->max_buy_ticket);
          if ($max_buy['status'] == 'true') {
            $check = true;
          } else {
            $check = false;
          }
        } else {
          $check = false;
        }

        if ($price->early_bird_discount == 'enable') {

          $start = Carbon::parse($price->early_bird_discount_date . $price->early_bird_discount_time);
          $end = Carbon::parse($price->early_bird_discount_date . $price->early_bird_discount_time);
          $today = Carbon::now();
          if ($today <= ($end)) {
            if ($price->early_bird_discount_type == 'fixed') {
              $early_bird_dicount = $price->early_bird_discount_amount;
            } else {
              $early_bird_dicount = ($price->early_bird_discount_amount * $total) / 100;
            }
          } else {
            $early_bird_dicount = 0;
          }
        } else {
          $early_bird_dicount = 0;
        }

        $information['total_early_bird_dicount'] = $early_bird_dicount * $data->quantity;
        $information['total'] = $total;
        $information['sub_total'] = $total;
        $information['quantity'] = $data->quantity;
      } elseif ($data->pricing_type == 'free') {
        $price = Ticket::where('event_id', $data->event_id)->select('max_buy_ticket')->first();
        //check guest checkout status enable or not
        if ($event_guest_checkout_status != 1) {
          //check max buy by customer
          $max_buy = isTicketPurchaseOnline($data->event_id, $price->max_buy_ticket);
          if ($max_buy['status'] == 'true') {
            $check = true;
          }
        }

        $information['quantity'] = $data->quantity;
        $information['total'] = 0;
        $information['sub_total'] = 0;
      }
    } else {
      $tickets = Ticket::where('event_id', $data->event_id)->select('id', 'title', 'pricing_type', 'price', 'variations', 'early_bird_discount', 'early_bird_discount_amount', 'early_bird_discount_type', 'early_bird_discount_date', 'early_bird_discount_time', 'normal_ticket_slot_unique_id', 'normal_ticket_slot_enable', 'free_tickete_slot_enable', 'free_tickete_slot_unique_id')->get();
      $ticketArr = [];

      foreach ($tickets as $key => $ticket) {
        if ($ticket->pricing_type == 'variation') {
          $varArr1 = json_decode($ticket->variations, true);
          foreach ($varArr1 as $key => $var1) {
            $stock[] = [
              'name' => $var1['name'],
              'price' => $var1['price'],
              'ticket_available' => $var1['ticket_available'] - $data->quantity[$key],
            ];
            //check early_bird discount
            if ($ticket->early_bird_discount == 'enable') {
              $start = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              $end = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              $today = Carbon::now();
              if ($today <= ($end)) {
                if ($ticket->early_bird_discount_type == 'fixed') {
                  $early_bird_dicount = $ticket->early_bird_discount_amount;
                } else {
                  $early_bird_dicount = ($var1['price'] * $ticket->early_bird_discount_amount) / 100;
                }
              } else {
                $early_bird_dicount = 0;
              }
            } else {
              $early_bird_dicount = 0;
            }

            $var1['type'] = $ticket->pricing_type;
            $var1['early_bird_dicount'] = $early_bird_dicount;
            $var1['ticket_id'] = $ticket->id;
            $ticketArr[] = $var1;
          }
          $information['stock'] =  $stock;
        } elseif ($ticket->pricing_type == 'normal') {
          //check early_bird discount
          if ($ticket->early_bird_discount == 'enable') {
            $start = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
            $end = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
            $today = Carbon::now();
            if ($today <= ($end)) {
              if ($ticket->early_bird_discount_type == 'fixed') {
                $early_bird_dicount = $ticket->early_bird_discount_amount;
              } else {
                $early_bird_dicount = ($ticket->price * $ticket->early_bird_discount_amount) / 100;
              }
            } else {
              $early_bird_dicount = 0;
            }
          } else {
            $early_bird_dicount = 0;
          }

          $language = Language::where('is_default', 1)->first();

          $ticketContent = TicketContent::where([['ticket_id', $ticket->id], ['language_id', $language->id]])->first();
          if (empty($ticketContent)) {
            $ticketContent = TicketContent::where('ticket_id', $ticket->id)->first();
          }

          $ticketArr[] = [
            'ticket_id' => $ticket->id,
            'early_bird_dicount' => $early_bird_dicount,
            'name' => $ticketContent->title,
            'price' => $ticket->price,
            'type' => $ticket->pricing_type,
            'slot_unique_id' => (int) $ticket->normal_ticket_slot_unique_id
          ];
        } elseif ($ticket->pricing_type == 'free') {
          $language = Language::where('is_default', 1)->first();
          $ticketContent = TicketContent::where([['ticket_id', $ticket->id], ['language_id', $language->id]])->first();
          if (empty($ticketContent)) {
            $ticketContent = TicketContent::where('ticket_id', $ticket->id)->first();
          }
          $ticketArr[] = [
            'ticket_id' => $ticket->id,
            'early_bird_dicount' => 0,
            'name' => $ticketContent->title,
            'price' => 0,
            'type' => $ticket->pricing_type,
            'slot_unique_id' => (int) $ticket->free_tickete_slot_unique_id
          ];
        }
      }

      $selTickets = [];
      foreach ($data->quantity as $key => $qty) {
        if ($qty > 0) {
          $selTickets[] = [
            'ticket_id' => $ticketArr[$key]['ticket_id'],
            'early_bird_dicount' => $qty * $ticketArr[$key]['early_bird_dicount'],
            'name' => $ticketArr[$key]['name'],
            'qty' => $qty,
            'price' => $ticketArr[$key]['price'],
          ];
        }
      }

      $sub_total = 0;
      $total_ticket = 0;
      $total_early_bird_dicount = 0;
      foreach ($selTickets as $key => $var) {
        $sub_total += $var['price'] * $var['qty'];
        $total_ticket += $var['qty'];
        $total_early_bird_dicount += $var['early_bird_dicount'];
      }


      //stock check
      foreach ($selTickets as $selTicket) {
        $stock = TicketStockCheck($selTicket['ticket_id'], $selTicket['qty'], $selTicket['name']);
        if ($stock == 'error') {
          $check = true;
          break;
        }
        //check guest checkout status enable or not
        if ($event_guest_checkout_status != 1) {
          $check_v = isTicketPurchaseVenueBackend($data->event_id, $selTicket['ticket_id'], $selTicket['name']);
          if ($check_v['status'] == 'true') {
            $check = true;
            break;
          }
        }
      }


      //check existins bookings seat or slot ids
      if (count($selected_slot_seat) > 0) {
        $check = $this->slotBookedDeactiveCheck($selected_slot_seat, $data->event_id);
      }


      $ticketArr =  collect($ticketArr)->map(function ($ticket) {
        $ticket['slot_unique_id'] = (int) $ticket['slot_unique_id'];
        return $ticket;
      })->toArray();


      $slotGroups = collect($ticketArr)
        ->groupBy('slot_unique_id')
        ->map(fn($items) => $items[0])
        ->toArray();



      $seat_total_amount = 0;
      $seat_early_bird_discount = 0;
      $seat_sub_total = 0;
      $seat_total_ticket = 0;
      foreach ($selected_seats as $seat) {
        $seat_total_ticket += 1;
        $slot_unique_id = (int) $seat['slot_unique_id'];
        $seat_total_amount += $seat['price'];
        $seat_sub_total += $seat['price'];
        $seat_early_bird_discount += $seat['discount'];
        $selTickets[] = [
          'ticket_id' => $seat['ticket_id'],
          'early_bird_dicount' => $seat['discount'],
          'name' => array_key_exists($slot_unique_id, $slotGroups) ? $slotGroups[$slot_unique_id]['name'] : "",
          'qty' => 1,
          'price' => $seat['price'],
          'discount' => $seat['discount'],
          'payable_price' => $seat['payable_price'],
          'seat_id' => $seat['id'],
          'seat_name' => $seat['name'],
          'slot_id' => $seat['slot_id'],
          'slot_name' => $seat['slot_name'],
          'slot_unique_id' => $slot_unique_id,
          'ticket_id' => $seat['ticket_id'],
          'event_id' => $seat['event_id'],
          's_type' => $seat['s_type'],
        ];
      }

      $total_early_bird_dicount +=  $seat_early_bird_discount;
      $sub_total += $seat_sub_total;
      $total = $sub_total - $total_early_bird_dicount;
      $total_ticket += $seat_total_ticket;

      $information['total'] = round($total, 2);
      $information['sub_total'] = round($sub_total, 2);
      $information['quantity'] = $total_ticket;
      $information['selTickets'] = $selTickets;
      $information['discount'] = null;
      $information['total_early_bird_dicount'] = round($total_early_bird_dicount, 2);
    }

    if ($check == true) {
      $information['success'] = false;
      $information['message'] = "Something went wrong..!";
      return  $information;
    }

    $information['success'] = true;

    return $information;
  }
  public function slotBookedDeactiveCheck($selectedSlotSeat, $event_id): bool
  {
    $check =  app(\App\Services\BookingServices::class)->checkBookingAndDeactiveSlotSeat($selectedSlotSeat, $event_id);
    return $check;
  }

  public function verifyPayment(Request $request)
  {
    $rules = [
      'gateway' => 'required',
      'total' => 'required',
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'validation_errors' => $validator->getMessageBag()->toArray()
      ]);
    }

    $amount = $request->total;
    $gateway = $request->gateway;

    //convert payment amount
    $currencyInfo  = Basic::select(
      'base_currency_symbol',
      'base_currency_symbol_position',
      'base_currency_text',
      'base_currency_text_position',
      'base_currency_rate'
    )
      ->firstOrFail();
    $gateway = strtolower($gateway);

    switch ($gateway) {
      case 'paypal':
        if ($currencyInfo->base_currency_text !== 'USD') {
          $rate = floatval($currencyInfo->base_currency_rate);
          $convertedTotal = $amount / $rate;
        }
        $paidAmount = $currencyInfo->base_currency_text === 'USD' ? $amount : $convertedTotal;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'paystack':
        if ($currencyInfo->base_currency_text !== 'NGN') {
          $information["success"] = true;
          $information["message"] = "";
          $information["paidAmount"] = 0.00;
          return ['success' => false, 'message' => 'Invalid currency for paystack payment.'];
        }
        $paidAmount = $amount * 100;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'flutterwave':
        $allowedCurrencies = array('BIF', 'CAD', 'CDF', 'CVE', 'EUR', 'GBP', 'GHS', 'GMD', 'GNF', 'KES', 'LRD', 'MWK', 'MZN', 'NGN', 'RWF', 'SLL', 'STD', 'TZS', 'UGX', 'USD', 'XAF', 'XOF', 'ZMK', 'ZMW', 'ZWD');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for flutterwave payment.'];
        }
        $paidAmount = intval($amount);

        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'razorpay':
        if ($currencyInfo->base_currency_text !== 'INR') {
          return ['success' => false, 'message' => 'Invalid currency for razorpay payment.'];
        }
        $paidAmount = $amount * 100;

        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'mercadopago':
        $allowedCurrencies = array('ARS', 'BOB', 'BRL', 'CLF', 'CLP', 'COP', 'CRC', 'CUC', 'CUP', 'DOP', 'EUR', 'GTQ', 'HNL', 'MXN', 'NIO', 'PAB', 'PEN', 'PYG', 'USD', 'UYU', 'VEF', 'VES');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for mercadopago payment.'];
        }
        $paidAmount = intval($amount);
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'mollie':
        $allowedCurrencies = array('AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for mollie payment.'];
        }
        $paidAmount = sprintf('%0.2f', $amount);
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'stripe':
        if ($currencyInfo->base_currency_text !== 'USD') {
          $rate = floatval($currencyInfo->base_currency_rate);
          $convertedTotal = round(($amount / $rate), 2);
        }

        $paidAmount = $currencyInfo->base_currency_text === 'USD' ? $amount : $convertedTotal;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'authorize.net':
        $allowedCurrencies = array('USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for authorize.net payment.'];
        }
        $paidAmount = sprintf('%0.2f', $amount);
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];

        break;
      case 'phonepe':
        $allowedCurrencies = array('INR');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for phonepe payment.'];
        }
        $paidAmount = $amount * 100;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'myfatoorah':
        $allowedCurrencies = array('KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for myfatoorah payment.'];
        }
        $paidAmount = intval($amount);
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'midtrans':
        $allowedCurrencies =  array('IDR');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for midtrans payment.'];
        }
        $paidAmount = (int)round($amount);
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'toyyibpay':
        $allowedCurrencies =  array('RM');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for toyyibpay payment.'];
        }
        $paidAmount = $amount * 100;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'xendit':
        $allowedCurrencies =  array('IDR', 'PHP', 'USD', 'SGD', 'MYR');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for xendit payment.'];
        }
        $paidAmount = $amount;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'monnify':
        $allowedCurrencies =  array('NGN');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for monnify payment.'];
        }
        $paidAmount = $amount;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      case 'now_payments':
        $allowedCurrencies =  array('USD', 'EUR', 'GBP', 'USDT', 'BTC', 'ETH');
        if (!in_array($currencyInfo->base_currency_text, $allowedCurrencies)) {
          return ['success' => false, 'message' => 'Invalid currency for now_payments payment.'];
        }
        $paidAmount = $amount;
        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
      default:
        $paidAmount = intval($amount);

        $information = [
          "success" => true,
          "message" => "",
          "paidAmount" => $paidAmount,
        ];
        break;
    }

    return $information;
  }

  public function earning_revenue($bookingInfo)
  {
    $earning = Earning::first();
    $earning->total_revenue = $earning->total_revenue + $bookingInfo->price + $bookingInfo->tax;
    if ($bookingInfo['organizer_id'] != null) {
      $earning->total_earning = $earning->total_earning + ($bookingInfo->tax + $bookingInfo->commission);
    } else {
      $earning->total_earning = $earning->total_earning + $bookingInfo->price + $bookingInfo->tax;
    }
    $earning->save();
    return;
  }

  public function checkoutVerify(Request $request)
  { {
      $rules = [
        'event_guest_checkout_status' => 'required',
        'event_id' => 'required',
        'seat_data' => 'nullable',
        'quantity' => 'required',
        'pricing_type' => 'required',
      ];
      $validator = Validator::make($request->all(), $rules);
      if ($validator->fails()) {
        return response()->json([
          'status' => false,
          'validation_errors' => $validator->getMessageBag()->toArray()
        ]);
      }

      $basic = Basic::select('event_guest_checkout_status', 'tax')->first();

      if ($request->event_guest_checkout_status == 1) {
        $bs_checkout_status = $basic->event_guest_checkout_status;
        if ($bs_checkout_status != 1) {
          return [
            'success' => false,
            'message' => 'login Required',
          ];
        }
      }

      $seat_data = $request->seat_data;
      $quantity = $request->quantity;
      $event_id = $request->event_id;
      $pricing_type = $request->pricing_type;
      $event_guest_checkout_status = $request->event_guest_checkout_status;


      $selected_seats = !empty($seat_data) ? $seat_data : [];

      $selected_slot_seat = collect($selected_seats)
        ->groupBy('slot_id')
        ->map(function ($group) {
          $first = $group->first();
          return [
            'slot_id' => $first['slot_id'],
            'slot_name' => $first['slot_name'],
            'event_id' => $first['event_id'],
            'ticket_id' => $first['ticket_id'],
            'slot_unique_id' => $first['slot_unique_id'],
            'slot_type' => $first['s_type'],
            'seats' => collect($group)->map(function ($seat) {
              return [
                'seat_id' => $seat['id'],
                'seat_name' => $seat['name'],
                'discount' => $seat['discount'],
                'price' => $seat['price'],
                'payable_price' => $seat['payable_price'],
              ];
            })->values()->toArray(),
          ];
        })
        ->map(function ($slot) {
          // count and sum for each slot
          $slot['seat_count'] = count($slot['seats']);
          $slot['seats_price'] = collect($slot['seats'])->sum('payable_price');
          return $slot;
        })
        ->values()
        ->toArray();

      $select = false;
      $event_type = Event::where('id', $event_id)->select('event_type')->first();


      if ($event_type->event_type == 'venue') {
        foreach ($quantity as $qty) {
          if ($qty > 0) {
            $select = true;
            break;
          }
          continue;
        }
        //slot validation for variations wise seats
        if (count($selected_slot_seat) > 0) {
          $select = true;
        }
      } else {
        if ($pricing_type == 'free') {
          $select = true;
          //free ticket validation
          if (count($selected_slot_seat) > 0) {
            $select = true;
          }
        } elseif ($pricing_type == 'normal') {
          if ($quantity == 0) {
            $select = false;
          } else {
            $select = true;
          }

          //when selected slot & seat pricing type normal
          if (count($selected_slot_seat) > 0) {
            $select = true;
          }
        } else {
          foreach ($quantity as $qty) {
            if ($qty > 0) {
              $select = true;
              break;
            }
            continue;
          }
        }
      }

      if ($select == false) {
        return [
          'success' => false,
          'message' => "Please Select at least one ticket ",
        ];
      }

      $information = [];
      $information['tax_type'] = 'percentage';
      $information['tax'] = $basic->tax;
      $information['selTickets'] = '';
      $event = Event::where('id', $event_id)->select('event_type', 'id')->first();

      $check = false;
      if ($event->event_type == 'online') {
        //**************** stock check start *************** */
        $stock = StockCheck($event_id, $quantity);
        if ($stock == 'error') {
          $check = true;
        }

        //*************** stock check end **************** */

        if ($pricing_type == 'normal') {
          $price = Ticket::where('event_id', $event_id)->select('price', 'early_bird_discount', 'early_bird_discount_amount', 'early_bird_discount_type', 'early_bird_discount_date', 'early_bird_discount_time', 'ticket_available', 'ticket_available_type', 'max_ticket_buy_type', 'max_buy_ticket')->first();
          $information['quantity'] = $quantity;
          $total = $quantity * $price->price;

          //check guest checkout status enable or not
          if ($event_guest_checkout_status != 1) {
            //check max buy by customer
            $max_buy = isTicketPurchaseOnline($event_id, $price->max_buy_ticket);
            if ($max_buy['status'] == 'true') {
              $check = true;
            } else {
              $check = false;
            }
          } else {
            $check = false;
          }

          if ($price->early_bird_discount == 'enable') {

            $start = Carbon::parse($price->early_bird_discount_date . $price->early_bird_discount_time);
            $end = Carbon::parse($price->early_bird_discount_date . $price->early_bird_discount_time);
            $today = Carbon::now();
            if ($today <= ($end)) {
              if ($price->early_bird_discount_type == 'fixed') {
                $early_bird_dicount = $price->early_bird_discount_amount;
              } else {
                $early_bird_dicount = ($price->early_bird_discount_amount * $total) / 100;
              }
            } else {
              $early_bird_dicount = 0;
            }
          } else {
            $early_bird_dicount = 0;
          }

          $information['total_early_bird_dicount'] = $early_bird_dicount * $quantity;
          $information['total'] = $total;
          $information['sub_total'] = $total;
          $information['quantity'] = $quantity;
        } elseif ($pricing_type == 'free') {
          $price = Ticket::where('event_id', $event_id)->select('max_buy_ticket')->first();
          //check guest checkout status enable or not
          if ($event_guest_checkout_status != 1) {
            //check max buy by customer
            $max_buy = isTicketPurchaseOnline($event_id, $price->max_buy_ticket);
            if ($max_buy['status'] == 'true') {
              $check = true;
            }
          }

          $information['quantity'] = $quantity;
          $information['total'] = 0;
          $information['sub_total'] = 0;
          $information['total_early_bird_dicount'] = 0.00;
        }
      } else {
        $tickets = Ticket::where('event_id', $event_id)->select('id', 'title', 'pricing_type', 'price', 'variations', 'early_bird_discount', 'early_bird_discount_amount', 'early_bird_discount_type', 'early_bird_discount_date', 'early_bird_discount_time', 'normal_ticket_slot_unique_id', 'normal_ticket_slot_enable', 'free_tickete_slot_enable', 'free_tickete_slot_unique_id')->get();
        $ticketArr = [];

        foreach ($tickets as $key => $ticket) {
          if ($ticket->pricing_type == 'variation') {
            $varArr1 = json_decode($ticket->variations, true);
            foreach ($varArr1 as $key => $var1) {
              $stock[] = [
                'name' => $var1['name'],
                'price' => $var1['price'],
                'ticket_available' => $var1['ticket_available'] - $quantity[$key],
              ];
              //check early_bird discount
              if ($ticket->early_bird_discount == 'enable') {
                $start = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
                $end = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
                $today = Carbon::now();
                if ($today <= ($end)) {
                  if ($ticket->early_bird_discount_type == 'fixed') {
                    $early_bird_dicount = $ticket->early_bird_discount_amount;
                  } else {
                    $early_bird_dicount = ($var1['price'] * $ticket->early_bird_discount_amount) / 100;
                  }
                } else {
                  $early_bird_dicount = 0;
                }
              } else {
                $early_bird_dicount = 0;
              }

              $var1['type'] = $ticket->pricing_type;
              $var1['early_bird_dicount'] = $early_bird_dicount;
              $var1['ticket_id'] = $ticket->id;
              $ticketArr[] = $var1;
            }
          } elseif ($ticket->pricing_type == 'normal') {
            //check early_bird discount
            if ($ticket->early_bird_discount == 'enable') {
              $start = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              $end = Carbon::parse($ticket->early_bird_discount_date . $ticket->early_bird_discount_time);
              $today = Carbon::now();
              if ($today <= ($end)) {
                if ($ticket->early_bird_discount_type == 'fixed') {
                  $early_bird_dicount = $ticket->early_bird_discount_amount;
                } else {
                  $early_bird_dicount = ($ticket->price * $ticket->early_bird_discount_amount) / 100;
                }
              } else {
                $early_bird_dicount = 0;
              }
            } else {
              $early_bird_dicount = 0;
            }

            $language = Language::where('is_default', 1)->first();

            $ticketContent = TicketContent::where([['ticket_id', $ticket->id], ['language_id', $language->id]])->first();
            if (empty($ticketContent)) {
              $ticketContent = TicketContent::where('ticket_id', $ticket->id)->first();
            }

            $ticketArr[] = [
              'ticket_id' => $ticket->id,
              'early_bird_dicount' => $early_bird_dicount,
              'name' => $ticketContent->title,
              'price' => $ticket->price,
              'type' => $ticket->pricing_type,
              'slot_unique_id' => (int) $ticket->normal_ticket_slot_unique_id
            ];
          } elseif ($ticket->pricing_type == 'free') {
            $language = Language::where('is_default', 1)->first();
            $ticketContent = TicketContent::where([['ticket_id', $ticket->id], ['language_id', $language->id]])->first();
            if (empty($ticketContent)) {
              $ticketContent = TicketContent::where('ticket_id', $ticket->id)->first();
            }
            $ticketArr[] = [
              'ticket_id' => $ticket->id,
              'early_bird_dicount' => 0,
              'name' => $ticketContent->title,
              'price' => 0,
              'type' => $ticket->pricing_type,
              'slot_unique_id' => (int) $ticket->free_tickete_slot_unique_id
            ];
          }
        }

        $selTickets = [];
        foreach ($quantity as $key => $qty) {
          if ($qty > 0) {
            $selTickets[] = [
              'ticket_id' => $ticketArr[$key]['ticket_id'],
              'early_bird_dicount' => $qty * $ticketArr[$key]['early_bird_dicount'],
              'name' => $ticketArr[$key]['name'],
              'qty' => $qty,
              'price' => $ticketArr[$key]['price'],
            ];
          }
        }

        $sub_total = 0;
        $total_ticket = 0;
        $total_early_bird_dicount = 0;
        foreach ($selTickets as $key => $var) {
          $sub_total += $var['price'] * $var['qty'];
          $total_ticket += $var['qty'];
          $total_early_bird_dicount += $var['early_bird_dicount'];
        }


        //stock check
        foreach ($selTickets as $selTicket) {
          $stock = TicketStockCheck($selTicket['ticket_id'], $selTicket['qty'], $selTicket['name']);
          if ($stock == 'error') {
            $check = true;
            break;
          }
          //check guest checkout status enable or not
          if ($event_guest_checkout_status != 1) {
            $check_v = isTicketPurchaseVenueBackend($event_id, $selTicket['ticket_id'], $selTicket['name']);
            if ($check_v['status'] == 'true') {
              $check = true;
              break;
            }
          }
        }


        //check existins bookings seat or slot ids
        if (count($selected_slot_seat) > 0) {
          $check = $this->slotBookedDeactiveCheck($selected_slot_seat, $event_id);
        }


        $ticketArr =  collect($ticketArr)->map(function ($ticket) {
          $ticket['slot_unique_id'] = (int) $ticket['slot_unique_id'];
          return $ticket;
        })->toArray();


        $slotGroups = collect($ticketArr)
          ->groupBy('slot_unique_id')
          ->map(fn($items) => $items[0])
          ->toArray();


        $seat_total_amount = 0;
        $seat_early_bird_discount = 0;
        $seat_sub_total = 0;
        $seat_total_ticket = 0;
        foreach ($selected_seats as $seat) {
          $seat_total_ticket += 1;
          $slot_unique_id = (int) $seat['slot_unique_id'];
          $seat_total_amount += $seat['price'];
          $seat_sub_total += $seat['price'];
          $seat_early_bird_discount += $seat['discount'];
          $selTickets[] = [
            'ticket_id' => $seat['ticket_id'],
            'early_bird_dicount' => $seat['discount'],
            'name' => array_key_exists($slot_unique_id, $slotGroups) ? $slotGroups[$slot_unique_id]['name'] : "",
            'qty' => 1,
            'price' => $seat['price'],
            'discount' => $seat['discount'],
            'payable_price' => $seat['payable_price'],
            'seat_id' => $seat['id'],
            'seat_name' => $seat['name'],
            'slot_id' => $seat['slot_id'],
            'slot_name' => $seat['slot_name'],
            'slot_unique_id' => $slot_unique_id,
            'ticket_id' => $seat['ticket_id'],
            'event_id' => $seat['event_id'],
            's_type' => $seat['s_type'],
          ];
        }

        $total_early_bird_dicount +=  $seat_early_bird_discount;
        $sub_total += $seat_sub_total;
        $total = $sub_total - $total_early_bird_dicount;
        $total_ticket += $seat_total_ticket;

        $information['total'] = round($total, 2);
        $information['sub_total'] = round($sub_total, 2);
        $information['quantity'] = $total_ticket;
        $information['selTickets'] = $selTickets;
        $information['total_early_bird_dicount'] = round($total_early_bird_dicount, 2);
      }

      if ($check == true) {
        return [
          'success' => false,
          'message' => 'Something Went Wrong...!'
        ];
      }
      $information['success'] = true;
      return $information;
    }
  }

  public function applyCoupon(Request $request)
  {
    $rules = [
      'coupon_code' => 'required',
      'price' => 'required',
      'total_early_bird_dicount' => 'required',
      'event_id' => 'required',
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'validation_errors' => $validator->getMessageBag()->toArray()
      ]);
    }
    $early_bird_dicount = $request->total_early_bird_dicount;
    $coupon_code = $request->coupon_code;
    $event_id = $request->event_id;
    $price = $request->price;

    $coupon = Coupon::where('code', $coupon_code)->first();
    if (!$coupon) {
      return [
        'success' => false,
        'message' => "Coupon is not valid",
        'discount' => 0.00,
      ];
    } else {

      $start = Carbon::parse($coupon->start_date);
      $end = Carbon::parse($coupon->end_date);
      $today = Carbon::now();
      $event_id = $request->event_id;
      $events = json_decode($coupon->events, true);
      if (!empty($events)) {
        if (in_array($event_id, $events)) {

          // if coupon is active
          if ($today->greaterThanOrEqualTo($start) && $today->lessThan($end)) {
            $value = $coupon->value;
            $type = $coupon->type;

            if ($early_bird_dicount != '') {
              $cartTotal = $price - $early_bird_dicount;
            } else {
              $cartTotal = $price  - $early_bird_dicount;
            }
            if ($type == 'fixed') {
              $couponAmount = $value;
            } else {
              $couponAmount = ($cartTotal * $value) / 100;
            }
            return [
              'success' => true,
              'message' => "Coupon applied successfully",
              'discount' => floatval($couponAmount),
            ];
          } else {
            return [
              'success' => false,
              'message' => "Coupon is not valid",
              'discount' => 0.00,
            ];
          }
        } else {
          return [
            'success' => false,
            'message' => "Coupon is not valid",
            'discount' => 0.00,
          ];
        }
      } else {
        // if coupon is active
        if ($today->greaterThanOrEqualTo($start) && $today->lessThan($end)) {
          $value = $coupon->value;
          $type = $coupon->type;
          if ($early_bird_dicount != '') {
            $cartTotal = $price - $early_bird_dicount;
          } else {
            $cartTotal =  $price - $early_bird_dicount;
          }
          if ($type == 'fixed') {
            $couponAmount = $value;
          } else {
            $couponAmount = ($cartTotal * $value) / 100;
          }
          return [
            'success' => true,
            'message' => "Coupon applied successfully",
            'discount' =>  floatval($couponAmount),
          ];
        } else {
          return [
            'success' => false,
            'message' => "Coupon applied successfully",
            'discount' => "Coupon is not valid",
          ];
        }
      }
    }
  }
}