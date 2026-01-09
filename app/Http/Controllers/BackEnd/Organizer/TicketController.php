<?php

namespace App\Http\Controllers\BackEnd\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Event;
use App\Models\Event\EventImage;
use App\Models\Event\EventContent;
use App\Models\Event\Ticket;
use App\Models\Event\TicketVariation;
use App\Http\Requests\Event\TicketRequest;
use App\Models\Event\Slot;
use App\Models\Event\SlotImage;
use App\Models\Event\TicketContent;
use App\Models\Event\VariationContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Validator;
use Purifier;

class TicketController extends Controller
{
  public function index(Request $request)
  {
    $evnt = Event::where('id', $request->event_id)->select('organizer_id')->firstOrFail();
    if (!($evnt) || $evnt->organizer_id != Auth::guard('organizer')->user()->id) {
      return redirect()->route('organizer.dashboard');
    }

    $languages = Language::all();

    $language = Language::where('code', $request->language)->firstOrFail();
    $information['language'] = $language;
    $event = EventContent::where('event_id', $request->event_id)->where('language_id', $language->id)->first();
    if (empty($event)) {
      $event = EventContent::where('event_id', $request->event_id)->first();
    }
    $tickets = Ticket::where('event_id', $request->event_id)->orderBy('id', 'desc')->get();
    $information['event'] = $event;

    $information['tickets'] = $tickets;
    return view('organizer.event.ticket.index', compact('information', 'languages'));
  }
  //create
  public function create(Request $request)
  {
    $evnt = Event::where('id', $request->event_id)->select('organizer_id')->firstOrFail();
    if (!($evnt) || $evnt->organizer_id != Auth::guard('organizer')->user()->id) {
      return redirect()->route('organizer.dashboard');
    }

    $languages = Language::get();
    $language = Language::where('code', $request->language)->firstOrFail();
    $event = EventContent::where('event_id', $request->event_id)->where('language_id', $language->id)->first();
    if (empty($event)) {
      $event = EventContent::where('event_id', $request->event_id)->first();
    }
    $information['languages'] = $languages;
    $information['event'] = $event;
    $eventType = Event::where('id', $request->event_id)->select('event_type')->first();
    $information['eventType'] = $eventType;
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    return view('organizer.event.ticket.create', $information);
  }
  //store
  public function store(TicketRequest $request)
  {
    $in = $request->all();
    $in['early_bird_discount'] = $request->early_bird_discount_type;
    $in['early_bird_discount_type'] = $request->discount_type;
    if ($request->pricing_type_2 == 'free') {
      $in['pricing_type'] = 'free';
      $in['price'] = 0;
      $in['free_tickete_slot_enable'] = 0;
      $in['free_tickete_slot_unique_id'] = rand(000000, 999999);
      $in['slot_seat_min_price'] = 0;

      $ticket =  Ticket::create($in);
    } elseif ($request->pricing_type_2 == 'normal') {
      $in['pricing_type'] = 'normal';
      $in['price'] = $request->price;
      $in['f_price'] = $request->price;
      $in['normal_ticket_slot_enable'] = 0;
      $in['normal_ticket_slot_unique_id'] = rand(000000, 999999);
      $in['slot_seat_min_price'] = 0;

      $ticket =  Ticket::create($in);
    } elseif ($request->pricing_type_2 == 'variation') {
      $in['pricing_type'] = 'variation';
      $f_price = max($request->variation_price);
      $in['f_price'] = $f_price;
      $variations = [];
      $languages = Language::get();
      foreach ($languages as $language) {
        if ($language->is_default == 1) {
          $variation_datas = $request[$language->code . '_variation_name'];
          if (!empty($variation_datas)) {
            foreach ($variation_datas as $key => $varName) {
              $variations[] = [
                'name' => $varName,
                'price' => $request->variation_price[$key],
                'ticket_available_type' => $request->v_ticket_available_type[$key],
                'ticket_available' => $request->v_ticket_available[$key],
                'max_ticket_buy_type' => $request->v_max_ticket_buy_type[$key],
                'v_max_ticket_buy' => $request->v_max_ticket_buy[$key],
                'slot_enable' => 0,
                'slot_unique_id' => rand(000000, 999999),
                'slot_seat_min_price' => 0,
              ];
            }
          }
        }
      }

      $variations = json_encode($variations);
      $in['variations'] = $variations;
      $ticket = Ticket::create($in);

      if ($request->pricing_type_2 == 'variation') {
        $languages = Language::get();
        foreach ($languages as $language) {
          $variation_datas = $request[$language->code . '_variation_name'];
          foreach ($variation_datas as $key => $data) {
            $variations_data['name'] = $data;
            $variations_data['key'] = $key;
            $variations_data['language_id'] = $language->id;
            $variations_data['ticket_id'] = $ticket->id;
            VariationContent::create($variations_data);
          }
        }
      }
    }

    $languages = Language::all();
    foreach ($languages as $language) {
      $data = [];
      $data['language_id'] = $language->id;
      $data['ticket_id'] = $ticket->id;
      $data['title'] = $request[$language->code . '_title'];
      $data['description'] = $request[$language->code . '_description'];
      TicketContent::create($data);
    }


    Session::flash('success', 'Added Successfully');

    return response()->json(['status' => 'success'], 200);
  }
  //edit
  public function edit(Request $request)
  {

    $evnt = Event::where('id', $request->event_id)->select('organizer_id')->firstOrFail();
    if (!($evnt) || $evnt->organizer_id != Auth::guard('organizer')->user()->id) {
      return redirect()->route('organizer.dashboard');
    }

    $ticket = Ticket::where('id', $request->id)->firstOrFail();
    $this->includeSlotSystemVariable($ticket->id);

    $language = Language::where('code', $request->language)->firstOrFail();
    $languages = Language::get();
    $information['languages'] = $languages;

    $event = EventContent::where('event_id', $request->event_id)->where('language_id', $language->id)->first();
    if (empty($event)) {
      $event = EventContent::where('event_id', $request->event_id)->first();
    }
    $information['event'] = $event;
    $ticket = Ticket::where('id', $request->id)->firstOrFail();
    $information['ticket'] = $ticket;
    $variations = json_decode($ticket->variations, true);
    $information['variations'] = $variations;
    $information['getCurrencyInfo']  = $this->getCurrencyInfo();
    $information['event_id'] = $request->event_id;
    $information['ticket_id'] = $ticket->id;

    return view('organizer.event.ticket.edit', $information);
  }
  //update
  public function update(TicketRequest $request)
  {


    $in = $request->except(['slot_seat_min_price']);
    $in['early_bird_discount'] = $request->early_bird_discount_type;
    $in['early_bird_discount_type'] = $request->discount_type;
    $in['ticket_available'] = $request->ticket_available_type == 'limited' ? $request->ticket_available : null;

    $in['max_buy_ticket'] = $request->max_ticket_buy_type == 'limited' ? $request->max_buy_ticket : null;

    if ($request->pricing_type_2 == 'free') {
      $in['pricing_type'] = 'free';
      $in['price'] = 0;
      $in['free_tickete_slot_enable'] = $request->free_tickete_slot_enable;
      $this->updateSlotIsEnable((int)$request->free_tickete_slot_unique_id, $request->free_tickete_slot_enable == "1" ? 1 : 0);
      $ticket =  Ticket::where('id', $request->ticket_id)->first();
      $ticket->update($in);
    } elseif ($request->pricing_type_2 == 'normal') {
      $in['pricing_type'] = 'normal';
      $in['price'] = $request->price;
      $in['f_price'] = $request->price;
      $in['normal_ticket_slot_enable'] = $request->slot_enable_no_vaidation;
      $this->updateSlotIsEnable((int)$request->slot_unique_id_no_vaidation, $request->slot_enable_no_vaidation == "1" ? 1 : 0);
      $ticket =  Ticket::where('id', $request->ticket_id)->first();
      $ticket->update($in);
    } elseif ($request->pricing_type_2 == 'variation') {
      $in['pricing_type'] = 'variation';
      $ticket =  Ticket::where('id', $request->ticket_id)->first();

      $this->deleteRemovedSlotIds($ticket->variations, $request->slot_unique_id_input);

      $languages = Language::get();
      $variations = [];
      foreach ($languages as $language) {
        if ($language->is_default == 1) {
          $variation_datas = $request[$language->code . '_variation_name'];
          if (!empty($variation_datas)) {
            foreach ($variation_datas as $key => $varName) {
              $variations[] = [
                'name' => $varName,
                'price' => $request->variation_price[$key],
                'ticket_available_type' => $request->v_ticket_available_type[$key],
                'ticket_available' => $request->v_ticket_available[$key],
                'max_ticket_buy_type' => $request->v_max_ticket_buy_type[$key],
                'v_max_ticket_buy' => $request->v_max_ticket_buy[$key],
                'slot_enable' => $request->slot_enable_input[$key] == "1" ? 1 : 0,
                'slot_unique_id' => (int)$request->slot_unique_id_input[$key],
                'slot_seat_min_price' => $request->slot_seat_min_price[$key],
              ];
              $this->updateSlotIsEnable((int)$request->slot_unique_id_input[$key], $request->slot_enable_input[$key] == "1" ? 1 : 0);
            }
          }
        }
      }

      $variations = json_encode($variations);
      $in['variations'] = $variations;
      $ticket->update($in);
      $languages = Language::get();
      foreach ($languages as $language) {
        $variation_datas = $request[$language->code . '_variation_name'];
        $variation_contents = VariationContent::where([['language_id', $language->id], ['ticket_id', $ticket->id]])->get();
        foreach ($variation_contents as $key => $variation_content) {
          $variation_content->delete();
        }

        foreach ($variation_datas as $key => $data) {
          $variations_data['name'] = $data;
          $variations_data['key'] = $key;
          $variations_data['language_id'] = $language->id;
          $variations_data['ticket_id'] = $ticket->id;
          VariationContent::create($variations_data);
        }
      }
    }

    $languages = Language::all();
    foreach ($languages as $language) {
      $ticket_content = TicketContent::where([['language_id', $language->id], ['ticket_id', $ticket->id]])->first();
      if (empty($ticket_content)) {
        $ticket_content = new TicketContent();
        $ticket_content->language_id = $language->id;
        $ticket_content->ticket_id = $ticket->id;
      }
      $ticket_content->title = $request[$language->code . '_title'];
      $ticket_content->description = $request[$language->code . '_description'];
      $ticket_content->save();
    }

    Session::flash('success', 'Event Ticket Update successfully!');

    return response()->json(['status' => 'success'], 200);
  }
  //destroy
  public function destroy(Request $request)
  {
    $ticket = Ticket::where('id', $request->id)->first();
    $ticket->delete();
    return redirect()->back()->with('success', 'Deleted Successfully');
  }
  //delete_variation
  public function delete_variation($id)
  {
    // $variation = TicketVariation::where('id', $id)->first();
    // $variation->delete();
    return 'success';
  }
  //bulk_delete
  public function bulk_delete(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $ticket = Ticket::where('id', $id)->first();
      $ticket->delete();
    }
    Session::flash('success', 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }
  public function includeSlotSystemVariable($ticket_id)
  {
    $ticket = Ticket::find($ticket_id);
    $organizer_id = $ticket->event->organizer_id;

    if (!is_null($ticket->variations)) {
      $variations = json_decode($ticket->variations, true);
      foreach ($variations as &$vari) {
        if (!array_key_exists('slot_enable', $vari)) {
          $vari['slot_enable'] = 0;
        }
        if (!array_key_exists('slot_unique_id', $vari)) {
          $vari['slot_unique_id'] = rand(000000, 999999);
        }
        if (!array_key_exists('slot_seat_min_price', $vari)) {
          $vari['slot_seat_min_price'] = 0.00;
        }
      }
      unset($vari);
      $ticket->update([
        'variations' => json_encode($variations)
      ]);
    }

    if (is_null($ticket->normal_ticket_slot_unique_id)) {
      $ticket->update([
        'normal_ticket_slot_unique_id' => rand(000000, 999999),
      ]);
    }

    if (is_null($ticket->normal_ticket_slot_enable)) {
      $ticket->update([
        'normal_ticket_slot_enable' => 0,
      ]);
    }

    if (is_null($ticket->free_tickete_slot_unique_id)) {
      $ticket->update([
        'free_tickete_slot_unique_id' => rand(000000, 999999),
      ]);
    }

    if (is_null($ticket->free_tickete_slot_enable)) {
      $ticket->update([
        'free_tickete_slot_enable' => 0,
      ]);
    }

    if (is_null($ticket->slot_seat_min_price)) {
      $ticket->update([
        'slot_seat_min_price' => 0.00
      ]);
    }

    return true;
  }

  public function deleteRemovedSlotIds($variationData, $incomingSlotIds)
  {
    $existingSlotIds = [];

    // Collect slot IDs from variations
    if (!empty($variationData)) {
      $decodedVariations = json_decode($variationData, true);
      $existingSlotIds = array_merge($existingSlotIds, array_column($decodedVariations, 'slot_unique_id'));
    }

    // Normalize incoming slot IDs to integers
    $normalizedIncomingSlotIds = array_map('intval', $incomingSlotIds);

    // Find slot IDs that exist but were not included in request
    $slotIdsToDelete = array_diff($existingSlotIds, $normalizedIncomingSlotIds);

    // Delete related slots and images
    foreach ($slotIdsToDelete as $slotId) {
      $slot = Slot::where('slot_unique_id', $slotId)->first();
      if (!is_null($slot)) {
        $slot->delete();
      }

      $slotImage = SlotImage::where('slot_unique_id', $slotId)->first();
      if (!is_null($slotImage)) {
        @unlink(public_path('assets/admin/img/map-image/' . $slotImage->image));
        $slotImage->delete();
      }
    }
  }

  public function updateSlotIsEnable($slot_unique_id, $slot_enable_input)
  {
    Slot::query()->where('slot_unique_id', $slot_unique_id)->update([
      'slot_enable' => $slot_enable_input
    ]);
    return true;
  }
}
