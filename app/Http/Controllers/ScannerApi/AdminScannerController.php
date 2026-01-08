<?php

namespace App\Http\Controllers\ScannerApi;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Event\Booking;
use App\Models\Event\EventDates;
use App\Models\Event\Ticket;
use App\Models\Event\Wishlist;
use App\Models\Language;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminScannerController extends Controller
{
  /* ********************************
     * Submit login for authentication
     * ********************************/
  public function loginSubmit(Request $request)
  {
    $rules = [
      'username' => 'required',
      'password' => 'required'
    ];
    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    // Attempt login manually using credentials
    $admin = Admin::where('username', $request->username)->first();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid credentials'
      ], 401);
    }

    if ($admin->status == 0) {
      return response()->json([
        'status' => 'error',
        'message' => 'Sorry, your account has been deactivated.'
      ], 403);
    }

    // Delete old tokens and create new one
    $admin->tokens()->where('name', $request->device_name ?? 'unknown-device')->delete();
    $token = $admin->createToken($request->device_name ?? 'unknown-device')->plainTextToken;


    $admin->image = !empty($admin->image) ?  asset('assets/admin/img/admins/' . $admin->image)  : asset('assets/admin/img/blank_user.jpg');

    Auth::guard('admin_sanctum')->user($admin);
    return response()->json([
      'status' => 'success',
      'admin' => $admin,
      'token' => $token
    ], 200);
  }
  public function check_qrcode(Request $request)
  {
    if (str_contains($request->booking_id, '__')) {
      $ids = explode('__', $request->booking_id);
      $booking_id = $ids[0];
      $unique_id = $ids[1];
      $check = Booking::where([['booking_id', $booking_id]])->first();
      if ($check) {
        // check payment status completed or not
        if ($check->paymentStatus == 'completed' || $check->paymentStatus == 'free') {
          //check scanned_tickets column empty or not
          if (is_null($check->scanned_tickets)) {
            $scannedTicketArr = [
              $unique_id
            ];
            $check->scanned_tickets = json_encode($scannedTicketArr);
            $check->save();
            return response()
            ->json([
              'alert_type' => 'success',
              'message' => 'Verified',
              'booking_id' => $request->booking_id
            ]);
          } else {
            //ticket random id will be insert
            $scannedTicketArr = json_decode($check->scanned_tickets, true);
            if (! in_array($unique_id, $scannedTicketArr)) {
              array_push($scannedTicketArr, $unique_id);
              $check->scanned_tickets = json_encode($scannedTicketArr);
              $check->save();
              return response()->json(
                ['alert_type' => 'success',
                 'message' => 'Verified',
                'booking_id' => $request->booking_id
              ]);
            } else {
              return response()->json(
                ['alert_type' => 'error',
                 'message' => 'Already Scanned',
                'booking_id' => $request->booking_id]
              );
            }
          }
        } elseif ($check->paymentStatus == 'pending') {
          return response()->json([
            'alert_type' => 'error',
            'message' => 'Payment incomplete',
            'booking_id' => $request->booking_id]
          );
        } elseif ($check->paymentStatus == 'rejected') {
          return response()->json(
            ['alert_type' => 'error',
             'message' => 'Payment Rejected',
            'booking_id' => $request->booking_id]
          );
        }
      } else {
        return response()->json(
          ['alert_type' => 'error',
          'message' => 'Unverified']
        );
      }
    } else {
      return response()->json(
        [
          'alert_type' => 'error',
         'message' => 'Unverified'
        ]);
    }
  }

  //check qr code
  public function logoutSubmit(Request $request)
  {
    $request->user()->currentAccessToken()->delete();
    return response()->json([
      'status' => 'success',
      'message' => 'Logout successfully'
    ], 200);
  }

  public function authentication_fail()
  {
    return response()->json([
      'success' => false,
      'message' => 'Unauthenticated.'
    ], 401);
  }


  public function events(Request $request, $id = null)
  {
    $locale = $request->header('Accept-Language');

    $ids = [];
    if (is_null($request->id)) {
      $ids = $this->adminEvents();
    } else {
      $ids[] = (int)$request->id;
    }

    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $information['events'] = DB::table('event_contents')
      ->join('events', 'events.id', '=', 'event_contents.event_id')
      ->where([
        ['event_contents.language_id', $language->id]
      ])
      ->whereIn('events.id', $ids)
      ->orderBy('events.created_at', 'desc')
      ->get()
      ->map(function ($event) use ($language) {
        return $this->formatEventForApi($event, $language);
      });

    $bookings = Booking::whereIn('event_id', $ids)->get();

    $information['total_attendees_tickets'] = $bookings->sum('quantity');
    $information['total_scanned_tickets'] = $bookings->map(function ($booking) {
      if (!is_null($booking->scanned_tickets)) {
        $scanned_tickets = json_decode($booking->scanned_tickets, true);
        return count($scanned_tickets);
      } else {
        return 0;
      }
    })->sum();
    $information['total_unscanned_tickets'] = $information['total_attendees_tickets'] - $information['total_scanned_tickets'];

    $all_tickets = $bookings->map(function ($booking) use (&$tickets) {
      $tickets = [];
      if (!is_null($booking->variation)) {
        $variations = json_decode($booking->variation, true);
        foreach ($variations as $variation) {
          $tickets[] = [
            'booking_id' => $booking->booking_id,
            'event_id' => $booking->event_id,
            'event_name' => optional($booking->event)->title,
            'ticket_name' => $variation['name'],
            'ticket_id' => $variation['unique_id'],
            'customer_phone' => $booking->phone,
            'payment_status' => $booking->paymentStatus,
            'scan_status' => !is_null($booking->scanned_tickets) ? (in_array($variation['unique_id'], json_decode($booking->scanned_tickets, true)) ? 'scanned' : 'unscanned') : 'unscanned',
          ];
        }
      } else {
        foreach (range(1, $booking->quantity) as $index) {
          $tickets[] = [
            'booking_id' => $booking->booking_id,
            'event_name' => optional($booking->event)->title,
            'event_id' => $booking->event_id,
            'ticket_id' => $index,
            'ticket_name' => null,
            'customer_phone' => $booking->phone,
            'payment_status' => $booking->paymentStatus,
            'scan_status' => !is_null($booking->scanned_tickets) ? (in_array($index, json_decode($booking->scanned_tickets, true)) ? 'scanned' : 'unscanned') : 'unscanned',
          ];
        }
      }
      return $tickets;
    })->flatten(1)   // all array merge
      ->values();

    $information['scanned_tickets'] = $all_tickets->where('scan_status', 'scanned')->values();
    $information['unscanned_tickets'] = $all_tickets->where('scan_status', 'unscanned')->values();
    $information['all_tickets'] = $all_tickets;

    return response()->json([
      'status' => 'success',
      'events' => $information
    ], 200);
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
  private function adminEvents()
  {
    $events = Event::pluck('id')->toArray();
    return $events;
  }
  public function ticketScanStatusChanged(Request $request)
  {
    $admin_id = Auth::guard('admin_sanctum')->user()->id;
    $rules = [
      'booking_id' => 'required',
      'ticket_id' => 'required',
      'status' => 'required|in:scanned,unscanned',
    ];
    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors(),
      ], 422);
    }
    $booking = Booking::where('booking_id', $request->booking_id)->first();
    if (!$booking) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid booking id',
      ], 404);
    }
    if (is_null($admin_id)) {
      return response()->json([
        'status' => 'error',
        'message' => 'You do not have permission',
      ], 403);
    }

    $scanned_tickets = !is_null($booking->scanned_tickets) ? json_decode($booking->scanned_tickets, true) : [];
    if ($request->status == 'scanned') {
      if (!in_array($request->ticket_id, $scanned_tickets)) {
        $scanned_tickets[] = $request->ticket_id;
      }
    } else {
      if (in_array($request->ticket_id, $scanned_tickets)) {
        $index = array_search($request->ticket_id, $scanned_tickets);
        unset($scanned_tickets[$index]);
      }
    }
    $booking->scanned_tickets = !empty($scanned_tickets) ? json_encode(array_values($scanned_tickets)) : null;
    $booking->save();
    return response()->json([
      'status' => 'success',
      'message' => 'Ticket scan status updated successfully',
    ], 200);
  }

}
