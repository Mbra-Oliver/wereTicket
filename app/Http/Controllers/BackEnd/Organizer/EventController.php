<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use Carbon\Carbon;
use App\Models\City;
use App\Models\Event;
use App\Models\State;
use App\Models\Country;
use App\Models\Language;
use App\Models\Event\Ticket;
use Illuminate\Http\Request;
use App\Models\Event\EventCity;
use App\Models\Event\EventDates;
use App\Models\Event\EventImage;
use App\Models\Event\EventState;
use App\Models\Event\EventContent;
use App\Models\Event\EventCountry;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Event\StoreRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Event\UpdateRequest;
use App\Http\Requests\TicketSettingRequest;

class EventController extends Controller
{
  //index
  public function index(Request $request)
  {
    $information['langs'] = Language::all();

    $language = Language::where('code', $request->language)->firstOrFail();
    $information['language'] = $language;

    $event_type = request()->input('event_type');
    $title = null;
    if (request()->filled('title')) {
      $title = request()->input('title');
    }


    $events = Event::join('event_contents', 'event_contents.event_id', '=', 'events.id')
      ->join('event_categories', 'event_categories.id', '=', 'event_contents.event_category_id')
      ->where('event_contents.language_id', '=', $language->id)
      ->when($title, function ($query) use ($title) {
        return $query->where('event_contents.title', 'like', '%' . $title . '%');
      })
      ->where('events.organizer_id', '=', Auth::guard('organizer')->user()->id)
      ->when($event_type, function ($query, $event_type) {
        return $query->where('events.event_type', $event_type);
      })
      ->select('events.*', 'event_contents.id as eventInfoId', 'event_contents.title', 'event_contents.slug', 'event_categories.name as category')
      ->orderByDesc('events.id')
      ->paginate(10);

    $information['events'] = $events;
    return view('organizer.event.index', $information);
  }
  //choose_event_type
  public function choose_event_type()
  {
    return view('organizer.event.event_type');
  }
  //online_event
  public function add_event()
  {
    // get all the languages from db
    $languages = Language::get();
    $countries = Country::get();
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['languages'] = $languages;
    $information['countries'] = $countries;
    return view('organizer.event.create', $information);
  }
  //city_state
  public function city_state($id)
  {
    $city = City::where('country_id', $id)->orderBy('name', 'asc')->get();
    $state = State::where('country_id', $id)->orderBy('name', 'asc')->get();

    $result = [];
    $result['city'] = $city;
    $result['state'] = $state;
    return $result;
  }

  public function gallerystore(Request $request)
  {
    $rules = [
      'file' => 'required|image|mimes:jpg,jpeg,png'
    ];
    $messages = [
      'file.required' => 'Please upload an image file.',
      'file.image'    => 'The uploaded file must be an image.',
      'file.mimes'    => 'Only jpg, jpeg, and png files are allowed.'
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      $validator->getMessageBag()->add('error', 'true');
      return response()->json($validator->errors(), 422);
    }

    $img = $request->file('file');
    list($width, $height) = getimagesize($img->getPathname());

    if ($width != 1170 || $height != 570) {
      return response()->json([
        'status'  => 'error',
        'msg' => 'The image dimensions must be exactly 1170x570 pixels.'
      ]);
    }

    $filename = uniqid() . '.jpg';
    $uploadPath = public_path('assets/admin/img/event-gallery/');
    if (!file_exists($uploadPath)) {
      @mkdir($uploadPath, 0775, true);
    }
    $img->move($uploadPath, $filename);

    $pi = new EventImage;
    $pi->event_id = $request->event_id ?? null;
    $pi->image = $filename;
    $pi->save();

    return response()->json([
      'status'  => 'success',
      'file_id' => $pi->id
    ]);
  }
  public function imagermv(Request $request)
  {
    $pi = EventImage::where('id', $request->fileid)->first();
    @unlink(public_path('assets/admin/img/event-gallery/') . $pi->image);
    $pi->delete();
    return $pi->id;
  }

  public function store(StoreRequest $request)
  {
    //calculate duration
    if ($request->date_type == 'single') {
      $start = Carbon::parse($request->start_date . $request->start_time);
      $end =  Carbon::parse($request->end_date . $request->end_time);
      $diffent = DurationCalulate($start, $end);
    }
    //calculate duration end

    $in = $request->all();
    $in['duration'] = $request->date_type == 'single' ? $diffent : '';

    $img = $request->file('thumbnail');

    $in['organizer_id'] = Auth::guard('organizer')->user()->id;
    if ($request->hasFile('thumbnail')) {
      $filename = time() . '.' . $img->getClientOriginalExtension();
      $directory = public_path('assets/admin/img/event/thumbnail/');
      @mkdir($directory, 0775, true);
      $request->file('thumbnail')->move($directory, $filename);
      $in['thumbnail'] = $filename;
    }
    $in['f_price'] = $request->price;
    $in['end_date_time'] = Carbon::parse($request->end_date . ' ' . $request->end_time);
    $event = Event::create($in);

    if ($request->date_type == 'multiple') {
      $i = 1;
      foreach ($request->m_start_date as $key => $date) {
        $start = Carbon::parse($date . $request->m_start_time[$key]);
        $end =  Carbon::parse($request->m_end_date[$key] . $request->m_end_time[$key]);
        $diffent = DurationCalulate($start, $end);

        EventDates::create([
          'event_id' => $event->id,
          'start_date' => $date,
          'start_time' => $request->m_start_time[$key],
          'end_date' => $request->m_end_date[$key],
          'end_time' => $request->m_end_time[$key],
          'duration' => $diffent,
          'start_date_time' => $start,
          'end_date_time' => $end,
        ]);
        if ($i == 1) {
          $event->update([
            'duration' => $diffent
          ]);
        }
        $i++;
      }

      //update event date time
      $event_date = EventDates::where('event_id', $event->id)->orderBy('end_date_time', 'desc')->first();

      $event->end_date_time = $event_date->end_date_time;
      $event->save();
    }




    $in['event_id'] = $event->id;
    if ($request->event_type == 'online') {
      if (!$request->pricing_type) {
        $in['pricing_type'] = 'normal';
      }
      $in['early_bird_discount'] = $request->early_bird_discount_type;
      $in['early_bird_discount_type'] = $request->discount_type;
      $ticket = Ticket::create($in);
    }

    $slders = $request->slider_images;

    foreach ($slders as $key => $id) {
      $event_image = EventImage::where('id', $id)->first();
      if ($event_image) {
        $event_image->event_id = $event->id;
        $event_image->save();
      }
    }
    $languages = Language::all();

    foreach ($languages as $language) {
      $event_content = new EventContent();
      $event_content->language_id = $language->id;
      $event_content->event_category_id = $request[$language->code . '_category_id'];
      $event_content->event_id = $event->id;
      $event_content->title = $request[$language->code . '_title'];
      if ($request->event_type == 'venue') {
        $event_content->address = $request[$language->code . '_address'];
        $event_content->country_id = $request[$language->code . '_country'];
        $event_content->city_id = $request[$language->code . '_city'];
        $event_content->state_id = $request[$language->code . '_state'];
        $event_content->zip_code = $request[$language->code . '_zip_code'];
      }
      $event_content->slug = createSlug($request[$language->code . '_title']);
      $event_content->description = Purifier::clean($request[$language->code . '_description'], 'youtube');
      $event_content->refund_policy = $request[$language->code . '_refund_policy'];
      $event_content->meta_keywords = $request[$language->code . '_meta_keywords'];
      $event_content->meta_description = $request[$language->code . '_meta_description'];
      $event_content->save();
    }


    Session::flash('success', 'Added Successfully');
    return response()->json(['status' => 'success'], 200);
  }
  /**
   * Update status (active/DeActive) of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateStatus(Request $request, $id)
  {
    $event = Event::find($id);

    if (Auth::guard('organizer')->user()->id != $event->organizer_id) {
      return back();
    }

    $event->update([
      'status' => $request['status']
    ]);
    Session::flash('success', 'Updated Successfully');

    return redirect()->back();
  }
  /**
   * Update featured status of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateFeatured(Request $request, $id)
  {
    $event = Event::find($id);
    if (Auth::guard('organizer')->user()->id != $event->organizer_id) {
      return back();
    }

    if ($request['is_featured'] == 'yes') {
      $event->is_featured = 'yes';
      $event->save();

      Session::flash('success', 'Updated Successfully');
    } else {
      $event->is_featured = 'no';
      $event->save();

      Session::flash('success', 'Updated Successfully');
    }

    return redirect()->back();
  }

  public function edit($id)
  {
    $event = Event::with('ticket')->where('id', $id)->firstOrFail();
    if (Auth::guard('organizer')->user()->id != $event->organizer_id) {
      return back();
    }

    if ($event->organizer_id != Auth::guard('organizer')->user()->id) {
      return redirect()->route('organizer.dashboard');
    }

    $information['event'] = $event;
    $mapStatus = DB::table('basic_settings')->pluck('google_map_status')->first();
    $defaultLang = Language::where('is_default', 1)->first();
    if ($mapStatus == 1) {
      $information['event_address'] = EventContent::select('address')
        ->where(['event_id' => $id, 'language_id' => $defaultLang->id])
        ->first();
    }

    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['languages'] = Language::all();
    // $information['countries'] = Country::get();
    // $information['cities'] = City::where('country_id',  $event->country)->get();
    // $information['states'] = State::where('country_id',  $event->country)->get();

    return view('organizer.event.edit', $information);
  }
  public function imagedbrmv(Request $request)
  {
    $pi = EventImage::where('id', $request->fileid)->first();
    $event_id = $pi->event_id;
    $image_count = EventImage::where('event_id', $event_id)->get()->count();
    if ($image_count > 1) {
      @unlink(public_path('assets/admin/img/event-gallery/') . $pi->image);
      $pi->delete();
      return $pi->id;
    } else {
      return 'false';
    }
    @unlink(public_path('assets/admin/img/event-gallery/') . $pi->image);
    $pi->delete();
    return $pi->id;
  }
  public function images($portid)
  {
    $images = EventImage::where('event_id', $portid)->get();
    return $images;
  }

  public function update(UpdateRequest $request)
  {
    //calculate duration
    if ($request->date_type == 'single') {
      $start = Carbon::parse($request->start_date . $request->start_time);
      $end =  Carbon::parse($request->end_date . $request->end_time);
      $diffent = DurationCalulate($start, $end);
    }
    //calculate duration end
    $img = $request->file('thumbnail');

    $in = $request->all();

    $event = Event::where('id', $request->event_id)->first();
    if ($request->hasFile('thumbnail')) {
      @unlink(public_path('assets/admin/img/event/thumbnail/') . $event->thumbnail);
      $filename = time() . '.' . $img->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event/thumbnail/'), 0775, true);
      $request->file('thumbnail')->move(public_path('assets/admin/img/event/thumbnail/'), $filename);
      $in['thumbnail'] = $filename;
    }

    $languages = Language::all();

    $i = 1;
    foreach ($languages as $language) {
      $event_content = EventContent::where('event_id', $event->id)->where('language_id', $language->id)->first();
      if (!$event_content) {
        $event_content = new EventContent();
      }
      $event_content->language_id = $language->id;
      $event_content->event_category_id = $request[$language->code . '_category_id'];
      $event_content->event_id = $event->id;
      $event_content->title = $request[$language->code . '_title'];
      if ($request->event_type == 'venue') {
        $event_content->address = $request[$language->code . '_address'];
        $event_content->country_id = $request[$language->code . '_country'];
        $event_content->city_id = $request[$language->code . '_city'];
        $event_content->state_id = $request[$language->code . '_state'];
        $event_content->zip_code = $request[$language->code . '_zip_code'];
      }
      $event_content->slug = createSlug($request[$language->code . '_title']);
      $event_content->description = Purifier::clean($request[$language->code . '_description'], 'youtube');
      $event_content->refund_policy = $request[$language->code . '_refund_policy'];
      $event_content->meta_keywords = $request[$language->code . '_meta_keywords'];
      $event_content->meta_description = $request[$language->code . '_meta_description'];
      $event_content->save();
    }
    if ($request->event_type == 'online') {
      if (!$request->pricing_type) {
        $pricing_type = 'normal';
      } else {
        $pricing_type = $request->pricing_type;
      }
      Ticket::where('event_id', $request->event_id)->update([
        'price' => $request->price,
        'f_price' => $request->price,
        'pricing_type' => $pricing_type,
        'ticket_available_type' => $request->ticket_available_type,
        'ticket_available' => $request->ticket_available,
        'max_ticket_buy_type' => $request->max_ticket_buy_type,
        'max_buy_ticket' => $request->max_buy_ticket,
        'early_bird_discount' => $request->early_bird_discount_type,
        'early_bird_discount_type' => $request->discount_type,
        'early_bird_discount_amount' => $request->early_bird_discount_amount,
        'early_bird_discount_date' => $request->early_bird_discount_date,
        'early_bird_discount_time' => $request->early_bird_discount_time,
      ]);
    }

    $event = Event::where('id', $event->id)->first();

    if ($request->date_type == 'multiple') {
      $i = 1;
      foreach ($request->m_start_date as $key => $date) {
        $start = Carbon::parse($date . $request->m_start_time[$key]);
        $end =  Carbon::parse($request->m_end_date[$key] . $request->m_end_time[$key]);
        $diffent = DurationCalulate($start, $end);

        if (!empty($request->date_ids[$key])) {
          $event_date = EventDates::where('id', $request->date_ids[$key])->first();
          $event_date->start_date = $date;
          $event_date->start_time = $request->m_start_time[$key];
          $event_date->end_date = $request->m_end_date[$key];
          $event_date->end_time = $request->m_end_time[$key];
          $event_date->duration = $diffent;
          $event_date->start_date_time = $start;
          $event_date->end_date_time = $end;
          $event_date->save();
        } else {
          EventDates::create([
            'event_id' => $event->id,
            'start_date' => $date,
            'start_time' => $request->m_start_time[$key],
            'end_date' => $request->m_end_date[$key],
            'end_time' => $request->m_end_time[$key],
            'duration' => $diffent,
            'start_date_time' => $start,
            'end_date_time' => $end,
          ]);
        }
        if ($i == 1) {
          $event->update([
            'duration' => $diffent
          ]);
        }
        $i++;
      }
    }

    if ($request->date_type == 'single') {
      $in['end_date_time'] = Carbon::parse($request->end_date . ' ' . $request->end_time);
      $in['duration'] = $diffent;
    } else {
      //update event date time
      $event_date = EventDates::where('event_id', $event->id)->orderBy('end_date_time', 'desc')->first();

      $in['end_date_time'] = $event_date->end_date_time;
    }

    $event->update($in);

    Session::flash('success', 'Updated Successfully');
    return response()->json(['status' => 'success'], 200);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $event = Event::find($id);
    if (Auth::guard('organizer')->user()->id != $event->organizer_id) {
      return back();
    }

    @unlink(public_path('assets/admin/img/event/thumbnail/') . $event->thumbnail);

    $event_contents = EventContent::where('event_id', $event->id)->get();
    foreach ($event_contents as $event_content) {
      $event_content->delete();
    }
    $event_images = EventImage::where('event_id', $event->id)->get();
    foreach ($event_images as $event_image) {
      @unlink(public_path('assets/admin/img/event-gallery/') . $event_image->image);
      $event_image->delete();
    }

    //bookings
    $bookings = $event->booking()->get();
    foreach ($bookings as $booking) {
      // first, delete the attachment
      @unlink(public_path('assets/admin/file/attachments/') . $booking->attachment);

      // second, delete the invoice
      @unlink(public_path('assets/admin/file/invoices/') . $booking->invoice);

      $booking->delete();
    }

    //tickets
    $tickets = $event->tickets()->get();
    foreach ($tickets as $ticket) {
      $ticket->delete();
    }

    //wishlists
    $wishlists = $event->wishlists()->get();
    foreach ($wishlists as $wishlist) {
      $wishlist->delete();
    }

    // finally delete the course
    $event->delete();

    return redirect()->back()->with('success', 'Deleted Successfully');
  }
  //bulk_delete
  public function bulk_delete(Request $request)
  {
    foreach ($request->ids as $id) {
      $event = Event::find($id);
      if (Auth::guard('organizer')->user()->id != $event->organizer_id) {
        return back();
      }

      @unlink(public_path('assets/admin/img/event/thumbnail/') . $event->thumbnail);

      $event_contents = EventContent::where('event_id', $event->id)->get();
      foreach ($event_contents as $event_content) {
        $event_content->delete();
      }
      $event_images = EventImage::where('event_id', $event->id)->get();
      foreach ($event_images as $event_image) {
        @unlink(public_path('assets/admin/img/event-gallery/') . $event_image->image);
        $event_image->delete();
      }

      //bookings
      $bookings = $event->booking()->get();
      foreach ($bookings as $booking) {
        // first, delete the attachment
        @unlink(public_path('assets/admin/file/attachments/') . $booking->attachment);

        // second, delete the invoice
        @unlink(public_path('assets/admin/file/invoices/') . $booking->invoice);

        $booking->delete();
      }

      //tickets
      $tickets = $event->tickets()->get();
      foreach ($tickets as $ticket) {
        $ticket->delete();
      }
      //wishlists
      $wishlists = $event->wishlists()->get();
      foreach ($wishlists as $wishlist) {
        $wishlist->delete();
      }

      // finally delete the course
      $event->delete();
    }
    Session::flash('success', 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }
  public function editTicketSetting($id)
  {
    $event = Event::where('organizer_id',  Auth::guard('organizer')->user()->id)->with('ticket')->findOrFail($id);
    $information['event'] = $event;
    return view('organizer.event.ticket-settings', $information);
  }
  public function updateTicketSetting(TicketSettingRequest $request)
  {

    $ticket_image = $request->file('ticket_image');
    $ticket_logo = $request->file('ticket_logo');
    $in = $request->all();
    $instructions = Purifier::clean($request->instructions);
    $event = Event::where('id', $request->event_id)->first();
    if ($request->hasFile('ticket_image')) {
      @unlink(public_path('assets/admin/img/event_ticket/') . $event->ticket_image);
      $filename = time() . rand(111, 999) . '.' . $ticket_image->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event_ticket/'), 0775, true);
      $request->file('ticket_image')->move(public_path('assets/admin/img/event_ticket/'), $filename);
      $in['ticket_image'] = $filename;
    }
    if ($request->hasFile('ticket_logo')) {
      @unlink(public_path('assets/admin/img/event_ticket_logo/') . $event->ticket_logo);
      $filename = time() . rand(111, 999) . '.' . $ticket_logo->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/event_ticket_logo/'), 0775, true);
      $request->file('ticket_logo')->move(public_path('assets/admin/img/event_ticket_logo/'), $filename);
      $in['ticket_logo'] = $filename;
    }
    $in['instructions'] = $instructions;

    $event->update($in);
    Session::flash('success', 'Updated Successfully');

    return response()->json(['status' => 'success'], 200);
  }





  //search country
  public function getCountry(Request $request)
  {
    $search = $request->input('search');
    $page = $request->input('page', 1);
    $pageSize = 10;

    $query = EventCountry::where('language_id', $request->lang);

    if ($search) {
      $query->where('name', 'like', "%{$search}%");
    }

    // Add pagination
    $countries = $query->skip(($page - 1) * $pageSize)
      ->take($pageSize + 1)
      ->get(['id', 'slug', 'name']);


    // Check if there's more data
    $hasMore = count($countries) > $pageSize;
    $results = $hasMore ? $countries->slice(0, $pageSize) : $countries;

    return response()->json([
      'results' => $results,
      'more' => $hasMore
    ]);
  }


  public function searchSate(Request $request)
  {
    $search = $request->input('search');
    $page = $request->input('page', 1);
    $pageSize = 10;

    $country_id = $request->country;

    $query = EventState::where('language_id', $request->lang)
      ->when($request->country, function ($q) use ($country_id) {
        return $q->where('country_id', $country_id);
      });

    if ($search) {
      $query->where('name', 'like', "%{$search}%");
    }

    // Add pagination
    $cities = $query->skip(($page - 1) * $pageSize)
      ->take($pageSize + 1)
      ->get(['id', 'slug', 'name']);

    // Check if there's more data
    $hasMore = count($cities) > $pageSize;
    $results = $hasMore ? $cities->slice(0, $pageSize) : $cities;

    return response()->json([
      'results' => $results,
      'more' => $hasMore
    ]);
  }


  public function getSearchCity(Request $request)
  {
    $search = $request->input('search');
    $page = $request->input('page', 1);
    $pageSize = 10;

    $state_id = $request->state;

    $query = EventCity::where('language_id', $request->lang)
      ->when($request->state, function ($q) use ($state_id) {
        return $q->where('state_id', $state_id);
      });

    if ($search) {
      $query->where('name', 'like', "%{$search}%");
    }

    // Add pagination
    $cities = $query->skip(($page - 1) * $pageSize)
      ->take($pageSize + 1)
      ->get(['id', 'slug', 'name']);

    // Check if there's more data
    $hasMore = count($cities) > $pageSize;
    $results = $hasMore ? $cities->slice(0, $pageSize) : $cities;

    return response()->json([
      'results' => $results,
      'more' => $hasMore
    ]);
  }


  /**
   * get cities or states
   */
  public function get_state(Request $request)
  {
    $event_state_status = DB::table('basic_settings')
      ->pluck('event_state_status')->first();

    //if event state status is off then return cities
    if ($event_state_status == 0) {
      $cities =  EventCity::where('country_id', $request->country_id)->exists();
      return response()->json(['cities' => $cities], 200);
    }

    //if event state status is on then return states
    $states =  EventState::where('country_id', $request->country_id)->exists();
    return response()->json(['states' => $states], 200);
  }

  /**
   * get cities
   */
  public function getcities(Request $request)
  {
    $cities =  EventCity::where('state_id', $request->state_id)->select('id', 'name')->get();

    if (count($cities) > 0) {
      return response()->json(['cities' => $cities], 200);
    }

    return response()->json(['cities' => 'no_data_found'], 200);
  }
}
