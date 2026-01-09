<?php
namespace App\Services;
use App\Models\Event\Booking;
use App\Models\FcmToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FirebaseService{

  public static function send($token, $booking_id, $title, $subtitle){
    $firebase_admin_json = DB::table('basic_settings')
      ->where('uniqid', 12345)
      ->value('firebase_admin_json');

    //initialize Firebase messaging service with service account
    $factory = (new Factory)
      ->withServiceAccount(public_path('assets/file/') . $firebase_admin_json);
    $messaging = $factory->createMessaging();

    $language = DB::table('languages')->where('is_default',1)->first();

    $booking = Booking::where('bookings.id', $booking_id)
    ->leftJoin('events', 'bookings.event_id', '=', 'events.id')
    ->leftJoin('event_contents', 'events.id', '=', 'event_contents.event_id')
    ->where('event_contents.language_id', $language->id)
    ->select('bookings.*', 'event_contents.title as event_title', 'event_contents.slug as event_slug')
    ->first();


    $body['event_id'] = $booking->event_id;
    $body['booking_id'] = $booking->id;

    $body['event_title'] = $booking->event_title;
    $body['event_slug'] = $booking->event_slug;

    $body['customer_fname'] = $booking->fname;
    $body['customer_lname'] = $booking->lname;
    $body['customer_email'] = $booking->email;
    $body['customer_phone'] = $booking->phone;
    $body['customer_country'] = $booking->country;
    $body['customer_state'] = $booking->state;
    $body['customer_city'] = $booking->city;
    $body['customer_zip_code'] = $booking->zip_code;
    $body['customer_address'] = $booking->address;

    $body['event_date'] = $booking->event_date;
    $body['tax_percentage'] = $booking->tax_percentage;
    $body['paymentMethod'] = $booking->paymentMethod;
    $body['paymentStatus'] = $booking->paymentStatus;

    $body['quantity'] = $booking->paymentStatus;

    $body['price'] = $booking->price;
    $body['tax'] = $booking->tax;
    $body['discount'] = $booking->discount;
    $body['early_bird_discount'] = $booking->early_bird_discount;

    try {
      //create and send FCM notification to the given device token
      $message = CloudMessage::withTarget('token', $token)
        ->withNotification(Notification::create($title, $subtitle))
        ->withData($body);
      $messaging->send($message);

    } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
      FcmToken::where('token', $token)->delete();
      return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
    return response()->json(['status' => 'success', 'message' => 'Notification sent successfully.']);
  }

  public static function pushNotification($title, $message,$buttonName, $buttonURL, $token)
  {
    $firebase_admin_json= DB::table('basic_settings')
      ->where('uniqid', 12345)
      ->value('firebase_admin_json');
    //initialize Firebase messaging service with service account
    $factory = (new Factory)
      ->withServiceAccount(public_path('assets/file/') . $firebase_admin_json);
    $messaging = $factory->createMessaging();
    $subtitle = Str::limit($message, 100, '...');
    $body['message'] = $message;
    $body['button_name'] = $buttonName;
    $body['button_url'] = $buttonURL;

    try {
      $message = CloudMessage::withTarget('token', $token)
        ->withNotification(Notification::create($title, $subtitle))
        ->withData($body);
      $messaging->send($message);
    } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
      FcmToken::where('token', $token)->delete();
      return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }

    return response()->json(['status' => 'success', 'message' => 'Notification sent successfully.']);
  }

}
