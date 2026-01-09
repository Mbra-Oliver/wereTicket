<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\Event;
use App\Models\Event\EventContent;
use App\Models\Event\Slot;
use App\Models\Event\SlotImage;
use App\Models\Event\SlotSeats;
use App\Models\Event\Ticket;
use App\Models\Event\TicketContent;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;


class SlotSeatController extends Controller
{

  public function allSlot(Request $request, $event_id, $ticket_id, $slot_unique_id)
  {
    $eventData = Event::find($event_id);
    $data['event_id'] = $event_id;
    $data['ticket_id'] = $ticket_id;
    $data['event_type'] = $eventData->event_type;
    $data['slot_unique_id'] = $slot_unique_id;
    $slotImage = SlotImage::query()->where([
      'event_id' => $event_id,
      'ticket_id' => $ticket_id,
      'slot_unique_id' => $slot_unique_id,
    ])->first();
    $language = Language::where('is_default', 1)->firstOrFail();
    $bookedTicketData =  app(\App\Services\BookingServices::class)->getBookedSlot($event_id);

    $slots = Slot::where([
      'event_id' => $event_id,
      'ticket_id' => $ticket_id,
      'slot_unique_id' => $slot_unique_id,
    ])->get();

    $slots->map(function ($slot) use ($bookedTicketData) {
      $slot->seats->each(function ($item) use ($bookedTicketData) {
        $item->is_booked = in_array($item->id, $bookedTicketData['seat_ids']) ? 1 : 0;
      });
      $check_booked = 0;
      if ($slot->type == 2) {
        $check_booked = in_array($slot->id, $bookedTicketData['slot_ids']) ? 1 : 0;
      } else {
        $check_booked = $slot->seats->count() == $slot->seats->where('is_booked', 1)->count() ? 1 : 0;
      }
      $slot->is_booked = $check_booked;
      $slot->slot_type = $slot->type;
      $slot->slot_name = $slot->name;
    });

    $data['slots'] = $slots;

    $data['event_contents'] = EventContent::where([
      'language_id' => $language->id,
      'event_id' => $event_id,
    ])->firstOrFail();

    $data['ticket_contents'] = TicketContent::where([
      'language_id' => $language->id,
      'ticket_id' => $ticket_id,
    ])->firstOrFail();
    $ticket = Ticket::where('id', $ticket_id)->firstOrFail();

    if ($ticket->pricing_type != $request->pricing_type) {
      Session::flash('warning', 'Before  change ticket pricing type then edit the seat map.');
      return back();
    }

    if ($ticket->pricing_type == 'variation') {
      $ticketVariation_ids_arrays = json_decode($ticket->variations, true);
      $variation = array_filter($ticketVariation_ids_arrays, function ($item) use ($slot_unique_id) {
        return $item['slot_unique_id'] == $slot_unique_id;
      });
      if (count($variation) < 0) return abort(404);
    } elseif ($ticket->pricing_type == 'normal') {
      if ($ticket->normal_ticket_slot_unique_id != $slot_unique_id) return abort(404);
    } elseif ($ticket->pricing_type == 'free') {
      if ($ticket->free_tickete_slot_unique_id != $slot_unique_id) return abort(404);
    } else {
      return abort(404);
    }

    $data['pricing_type'] = $ticket->pricing_type;
    $data['cover_image'] = !is_null($slotImage) ? $slotImage->image : null;

    return view('organizer.event.ticket.slots.index', $data);
  }

  public function storeBackgroundImage(Request $request)
  {
    $event_id = $request->event_id;
    $ticket_id = $request->ticket_id;
    $slot_unique_id = $request->slot_unique_id;

    $slotImage =  SlotImage::where([
      'event_id' => $event_id,
      'ticket_id' => $ticket_id,
      'slot_unique_id' => $slot_unique_id,
    ])->first();

    $img = $request->file('map_image');
    $allowedExts = array('jpg', 'png', 'jpeg', 'avif', 'webp');
    $rules = [
      'map_image' => [
        !is_null($slotImage) ? "nullable" : 'required',
        function ($attribute, $value, $fail) use ($img, $allowedExts) {
          $ext = $img->getClientOriginalExtension();
          if (!in_array($ext, $allowedExts)) {
            return $fail("Only png, jpg, jpeg, avif images are allowed");
          }
        }
      ],
      'slot_unique_id' => 'required',
      'ticket_id' => 'required',
      'event_id' => 'required',
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()->toArray()
      ], 400);
    }

    if ($request->has('map_image')) {
      $filename = uniqid() . '.jpg';
      @mkdir(public_path('assets/admin/img/map-image/'), 0775, true);
      $img->move(public_path('assets/admin/img/map-image/'), $filename);

      $slotImage = SlotImage::where(
        [
          'event_id' => $event_id,
          'ticket_id' => $ticket_id,
          'slot_unique_id' => $slot_unique_id,
        ]
      )
        ->first();

      if (!is_null($slotImage)) {
        @unlink(public_path('assets/admin/img/map-image/' . $slotImage->image));
      }
      SlotImage::updateOrCreate([
        'event_id' => $event_id,
        'ticket_id' => $ticket_id,
        'slot_unique_id' => $slot_unique_id,
      ], [
        'image' =>  $filename
      ]);

      Session::flash('success', "Image Update Successfully !!");
    }
    return response()->json(['status' => 'success'], 200);
  }

  public function slotStoreUpdate(Request $request)
  {
    $ticket = Ticket::findorFail($request->ticket_id);
    $early_bird_discount_is_enable = $ticket->early_bird_discount == 'enable' ? true : false;
    $early_bird_discount_type = $ticket->early_bird_discount_type;
    $early_bird_discount_amount = $ticket->early_bird_discount_amount + 1;

    if ($early_bird_discount_is_enable == true &&  $early_bird_discount_type == 'fixed' && $request->slot_type == 2 && $request->price < $early_bird_discount_amount) {
      $early_bird_discount_min_amount = $early_bird_discount_amount;
    }

    $rules = [
      'pos_x' => 'required',
      'pos_y' => 'required',
      'rotate' => 'required',
      'background_color' => 'required',
      'width' => 'required',
      'height' => 'required',
      'round' => 'required',
      'slot_name' => 'required',
      'slot_type' => 'required',
      'number_of_seat' => 'required',
      'event_id' => 'required',
      'ticket_id' => 'required',
      'slot_unique_id' => 'required',
      'font_size' => 'required',
      'pricing_type' => 'required',
    ];

    if ($early_bird_discount_is_enable && isset($early_bird_discount_min_amount)) {
      $rules['price'][] = 'numeric';
      $rules['price'][] = 'min:' . $early_bird_discount_min_amount;
    }

    $messages = [
      'pos_x.required' => __('Enter your x position field is required'),
      'pos_y.required' =>  __('Enter your y position field is required.'),
      'rotate.required' =>  __('Enter your rotate field is required.'),
      'background_color.required' =>  __('Enter your background colur field is required.'),
      'width.required' =>  __('Enter width field is required.'),
      'height.required' =>  __('Enter height field is required.'),
      'round.required' =>  __('Enter Round Shape field is required.'),
      'slot_name.required' =>  __('Enter Slot Name field is required.'),
      'slot_type.required' =>  __('Select Slot type field is required.'),
      'number_of_seat.required' =>  __('Enter number of seats field is required.'),
      'price.required_if' =>  __('Price is required when seat type is Auto Select All Seats.'),
      'font_size.required' =>  __('Enter your font size field is required.'),
      'event_id.required' =>  __('Event ID is missing.'),
      'ticket_id.required' =>  __('Ticket ID is required.'),
      'slot_unique_id.required' =>  __('Slot unique ID is required.'),
      'pricing_type.required' =>  __('Pricing Type is required.'),
    ];

    if ($early_bird_discount_is_enable && isset($early_bird_discount_min_amount)) {
      $messages['price.min'] =  __('Early Bird Discount is enabled. The price must be at least :') . ' ' . $early_bird_discount_min_amount  . '.';
    }

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    $slot = Slot::updateOrCreate([
      'event_id' => $request->event_id,
      'ticket_id' => $request->ticket_id,
      'slot_unique_id' => $request->slot_unique_id,
      'id' => $request->slot_id
    ], [
      'pos_x' => $request->pos_x,
      'pos_y' => $request->pos_y,
      'rotate' => $request->rotate,
      'pricing_type' => $request->pricing_type,
      'background_color' => $request->background_color,
      'width' => $request->width,
      'height' => $request->height,
      'round' => $request->round,
      'name' => $request->slot_name,
      'type' => $request->slot_type,
      'number_of_seat' => $request->number_of_seat,
      'price' => $request->pricing_type == 'free' ? 0.00 : $request->price,
      'font_size' => $request->font_size,
      'is_deactive' => $request->slot_deactive,
    ]);

    $number_format_start = 0;
    //seat map slot not exits. it first time create
    if ($slot->wasRecentlyCreated == true) {
      $totalSeats =  $slot->number_of_seat;
      for ($i = 1; $i <= $totalSeats; $i++) {
        $seatNumber = str_pad($number_format_start + $i, 2, '0', STR_PAD_LEFT);
        SlotSeats::create([
          'slot_id' =>  $slot->id,
          'name'    =>  $slot->name . '-' . $seatNumber,
        ]);
      }
    }

    $checkSlotMap = $slot->seats()->count() - $slot->number_of_seat;
    if ($slot->wasRecentlyCreated == false && $checkSlotMap != 0) {
      //when the slot is reduced
      if ($checkSlotMap > 0) {
        $slot->seats()
          ->orderBy('id', 'desc')
          ->limit($checkSlotMap)
          ->delete();
      } else {
        //when  the slot is increased
        $increasedSlot = abs($checkSlotMap);
        $currentSlotAvailable = $slot->seats()->count();
        //updated slot type
        for ($i = 1; $i <= $increasedSlot; $i++) {
          $seatNumber = str_pad($number_format_start + $i + $currentSlotAvailable, 2, '0', STR_PAD_LEFT);
          SlotSeats::create([
            'slot_id' => $slot->id,
            'name'   => $slot->name . '-' . $seatNumber,
          ]);
        }
      }
    }

    //updated per price when slot type ==2

    if ($slot->type == 2 && $request->pricing_type != 'free') {
      $total_price = $slot->price;
      $total_seat = $slot->seats()->count();
      $pricePerSeat = round($total_price / $total_seat, 2);
      $seatPricesArr = [];
      $sum = 0;

      for ($i = 0; $i < ($total_seat - 1); $i++) {
        $seatPricesArr[] = $pricePerSeat;
        $sum += $pricePerSeat;
      }

      $lastSeatPrice = round($total_price - $sum, 2);
      $seatPricesArr[] = $lastSeatPrice;

      foreach ($slot->seats()->get() as $key => $seat) {
        $seat->update(['price' => $seatPricesArr[$key]]);
      }
    }


    $ticket_id = $request->ticket_id;
    $slot_unique_id = $request->slot_unique_id;
    $this->minPriceTicetUpdate($ticket_id, $slot_unique_id);

    //retirm this data
    $slot->slot_name = $slot->name;
    $slot->slot_type = $slot->type;
    return response()->json($slot);
  }

  public function slotDrupDrop(Request $request)
  {
    $slot_id = $request->slot_id;
    $input['pos_x'] = $request->pos_x;
    $input['pos_y'] = $request->pos_y;
    $slot = Slot::find($slot_id)->update($input);
    return response()->json($slot);
  }

  public function slotDelete(Request $request)
  {
    $slot_id = $request->slot_id;
    $slot = Slot::find($slot_id);
    if ($slot) {
      $slot->delete();
    }
    return response()->json($slot);
  }

  public function slotSeat(Request $request)
  {
    $slot_id = $request->slot_id;
    $slot = Slot::query()
      ->where('id', $slot_id)
      ->with('seats')
      ->first();
    $data['event_id'] = $request->event_id;
    $data['ticket_id'] = $request->ticket_id;
    $data['slot_unique_id'] = $request->slot_unique_id;
    $data['slot_id'] = $slot_id;
    $data['slot_type'] = $slot->type;
    $data['slot'] = $slot;
    $bookedTicketData =  app(\App\Services\BookingServices::class)->getBookedSlot($slot->event_id);
    $data['bookedSeats'] =  $bookedTicketData['seat_ids'];
    return response()->json([
      'status' => 'Success',
      'message' => 'Success',
      'view' => view('organizer.event.ticket.slots.form-data', $data)->render()
    ]);
  }


  public function slotSeatUpdate(Request $request)
  {

    $settings = Basic::first();
    $ticket = Ticket::findorFail($request->ticket_id);
    $minimum_early_bird_discount_amount = 0.00;
    $message_early_bird_discount = false;
    $message_key_price = false;
    $message_key_name = false;

    foreach ($request->keyName as $name) {
      if (empty($name)) {
        $message_key_name = true;
        break;
      }
    }
    //when slot type ==1
    if ($request->slot_type == 1 && $request->pricing_type != 'free') {
      if ($ticket->early_bird_discount == 'enable' && $ticket->early_bird_discount_type == 'fixed') {
        if (min($request->keyPrice) <= $ticket->early_bird_discount_amount) {
          $minimum_early_bird_discount_amount = $ticket->early_bird_discount_amount + 1;
        }
      }
      foreach ($request->keyPrice as $price) {
        if (empty($price)) {
          $message_key_price = true;
          break;
        }
      }
      if ($ticket->early_bird_discount == 'enable' && $ticket->early_bird_discount_type == 'fixed') {
        foreach ($request->keyPrice as $price) {
          if ($price < $minimum_early_bird_discount_amount) {
            $message_early_bird_discount = true;
            break;
          }
        }
      }
    }

    $rules = [
      'key_name' => $message_key_name == true ? 'required' : '',
      'key_price' => $message_key_price == true ? 'required' : '',
      'early_bird_discount' => $message_early_bird_discount == true ? 'required' : ''
    ];

    $messages = [];
    $messages['key_name.required'] = 'All seats name field is required';
    $messages['key_price.required'] = 'All seats price field is required';
    $messages['early_bird_discount.required'] = 'early bird discount is enabled. The price must be at least' . ' ' . $minimum_early_bird_discount_amount . ' ' . $settings->base_currency_text;

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    $nameKeys = $request->keyName;
    $price_keys = $request->keyPrice;
    $is_deactive = $request->slot_deactive_input;

    foreach ($nameKeys as $id => $name) {
      $updateData = [
        'name' => $name,
        'is_deactive' => $is_deactive[$id],
      ];

      if ($request->slot_type == 1) {
        $updateData['price'] = $price_keys[$id] ?? 0.00;
      }
      SlotSeats::query()->where('id', $id)->update($updateData);
    }

    if ($request->slot_type == 1) {
      Slot::find($request->slot_id)->update([
        'price' => $request->pricing_type == 'free' ? 0.00 : min($price_keys)
      ]);
    }

    $ticket_id = $request->ticket_id;
    $slot_unique_id = $request->slot_unique_id;
    $this->minPriceTicetUpdate($ticket_id, $slot_unique_id);
    Session::flash('success', __('Seat updated Successfully!'));
    return response()->json(['status' => 'success']);
  }

  public function setPrice($amount, $slotNumber)
  {
    return number_format($amount / $slotNumber, 2);
  }

  public function minPriceTicetUpdate($ticket_id, $slot_unique_id)
  {
    $ticket = Ticket::find($ticket_id);

    $slot_price = Slot::where('slot_unique_id', $slot_unique_id)
    ->where('ticket_id',$ticket->id)
    ->where('price','>', 0)
    ->orderBy('price','asc')
    ->first();


    $slot_min_price = !empty($slot_price) ? $slot_price->price : 0.00;

    if ($ticket->pricing_type == 'variation' && !is_null($ticket->variations)) {
      $variations = collect(json_decode($ticket->variations, true))
        ->map(function ($vari) use ($slot_min_price, $slot_unique_id) {
          if($vari['slot_unique_id']  == $slot_unique_id){
            $vari['slot_seat_min_price'] = $slot_min_price;
          }
            return $vari;
        })
        ->toArray();
      $ticket->update([
        'variations' => json_encode($variations)
      ]);
    }elseif($ticket->pricing_type == 'normal'){
      $ticket->update([
        'slot_seat_min_price' => $slot_min_price
      ]);
    } else {
      $ticket->update([
        'slot_seat_min_price' => 0.00
      ]);
    }
  }
}
