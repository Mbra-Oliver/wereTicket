<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BasicSettings\PageHeading;
use App\Models\Event\EventCategory;
use App\Models\Event\EventDates;
use App\Models\Event\Ticket;
use App\Models\Event\Wishlist;
use App\Models\Language;
use App\Models\Organizer;
use App\Traits\ApiFormatTrait;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PHPMailer\PHPMailer\PHPMailer;
use stdClass;

class OrganizerController extends Controller
{
  use ApiFormatTrait;
  /* ****************************
     * Show Organizers
     * ****************************/
  public function index(Request $request)
  {
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $information['page_title'] = PageHeading::where('language_id', $language->id)->pluck('organizer_page_title')->first();

    $organizer_name = null;
    $organizer_query = Organizer::query()
      ->join('organizer_infos', 'organizers.id', '=', 'organizer_infos.organizer_id');
    if ($request->filled('organizer_name')) {
      $organizer_name = $request->organizer_name;

      $organizer_query = $organizer_query->where('name', 'like', '%' . $organizer_name . '%');
    }

    $data['organizers'] = $organizer_query
      ->where('organizer_infos.language_id', $language->id)
      ->select('organizer_infos.*', 'organizers.username', 'organizers.id', 'organizers.photo', 'organizers.phone', 'organizers.email', 'organizers.facebook', 'organizers.twitter', 'organizers.linkedin', 'organizers.status')
      ->get()
      ->map(function ($organizer) {
        $data = $this->format_organizer_data_2($organizer);
        $data->total_events = OrganizerEventCount($organizer->id);
        return $data;
      });

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  public function details(Request $request, $id)
  {

    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $information = [];
    $categories = EventCategory::where('status', 1)
      ->where('language_id', $language->id)
      ->orderBy('serial_number', 'asc')->get();

    if (filled($request->admin)) {
      $information['admin'] = true;
      $admin = Admin::first();

      $information['organizer'] = $this->format_organizer_data($admin, 'admin');

      foreach ($categories as $category) {
        $events = DB::table('event_contents')
          ->join('events', 'events.id', '=', 'event_contents.event_id')
          ->where([
            ['event_contents.language_id', $language->id],
            ['event_contents.event_category_id', $category->id],
            ['events.status', 1],
            ['events.organizer_id', null],
          ])
          ->orderBy('events.created_at', 'desc')
          ->get()
          ->map(function ($event) use ($language) {
            return $this->formatEventForApi($event, $language);
          });

        $information['events']['categories'][$category->id] = $events;
      }
    } else {
      //organizer info
      $information['admin'] = false;
      $organizer = Organizer::leftJoin('organizer_infos', function ($join) use ($language) {
        $join->on('organizers.id', '=', 'organizer_infos.organizer_id')
          ->where('organizer_infos.language_id', '=', $language->id);
      })
        ->where('organizers.id', $id)
        ->select(
          'organizers.id',
          'organizers.photo',
          'organizers.phone',
          'organizers.email',
          'organizers.username',
          'organizers.status',
          'organizers.facebook',
          'organizers.twitter',
          'organizers.linkedin',
          'organizer_infos.name as organizer_name',
          'organizer_infos.country',
          'organizer_infos.city',
          'organizer_infos.state',
          'organizer_infos.zip_code',
          'organizer_infos.address',
          'organizer_infos.designation',
          'organizer_infos.details',
        )
        ->first();
      $information['organizer'] = $this->format_organizer_data($organizer, 'organizer');
      //end organizer info

      foreach ($categories as $category) {
        $events = DB::table('event_contents')
          ->join('events', 'events.id', '=', 'event_contents.event_id')
          ->where([
            ['event_contents.language_id', $language->id],
            ['event_contents.event_category_id', $category->id],
            ['events.status', 1],
            ['events.organizer_id', $id],
          ])
          ->orderBy('events.created_at', 'desc')
          ->get()
          ->map(function ($event) use ($language) {
            return $this->formatEventForApi($event, $language);
          });

        $information['events']['categories'][$category->id] = $events;
      }
    }

    $information['categories'] = $categories;


    // $information['events'] = $events;

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

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

  public function contactMail(Request $request)
  {

    $info = DB::table('basic_settings')
      ->select('website_title', 'smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name', 'email_address')
      ->first();

    $rules = [
      'organizer_id' => 'required',
      'name' => 'required',
      'email' => 'required',
      'subject' => 'required',
      'message' => 'required'
    ];
    $msgs = [];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $organizer = Organizer::where('id', $request->organizer_id)->first();

    $name = $request->name;
    $subject = $request->subject;

    $message = '<p>Message : ' . $request->message . '</p> <p><strong>Enquirer Name: </strong>' . $name . '<br/><strong>Enquirer Mail: </strong>' . $request->email . '</p>';

    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

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

    try {
      $mail->setFrom($info->from_mail, $info->from_name);
      $mail->addAddress($organizer->email);

      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $message;

      $mail->send();

      $status = true;
      $message = 'Your contact request send to organizer successfully.';

    } catch (\Exception $e) {
      $status = false;
      $message = 'Something went wrong';
    }

    return response()->json([
      'success' => $status ?? false,
      'message' => $message ?? "something went wrong!"
    ]);

  }
}
