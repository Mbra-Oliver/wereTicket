<?php

namespace App\Services;

use App\Models\Event\Booking;
use App\Models\Event\Slot;
use App\Models\Event\SlotSeats;

class BookingServices
{
  public function getBookedSlot(int $eventId)
  {
    $slotUniqueIds = [];
    $slotIds = [];
    $seatIds = [];
    $bookedTickets = Booking::where('event_id', $eventId)
      ->get();
    foreach ($bookedTickets as $ticket) {
      if (!empty($ticket->variation)) {
        $data = json_decode($ticket->variation, true);
        $slotIds[] = array_column($data, 'slot_id');
        $seatIds[] = array_column($data, 'seat_id');
        $slotUniqueIds[] = array_column($data, 'slot_unique_id');
      }
    }
    $slotIds_2 = array_values(array_unique(array_merge(...$slotIds)));
    $seatIds_2 = array_values(array_unique(array_merge(...$seatIds)));
    $slotUniqueIds_2 = array_values(array_unique(array_merge(...$slotUniqueIds)));

    return [
      'slot_ids' => $slotIds_2,
      'seat_ids' => $seatIds_2,
      'slot_unique_ids' => $slotUniqueIds_2,
    ];
  }

  public function showSlot(int $eventId, int $ticketId, int $slotUniqueId)
  {
    $bookedData = $this->getBookedSlot($eventId);
    $slot = null;

    // --- TYPE 2 SLOT HANDLING --- type is select all seats
    $slotTypeTwoIds = Slot::where([
      'slot_unique_id' => $slotUniqueId,
      'ticket_id'      => $ticketId,
      'event_id'       => $eventId,
      'is_deactive'    => 0,
      'type'           => 2,
    ])
      ->pluck('id')
      ->toArray();

      $availableTypeTwoIds = array_diff($slotTypeTwoIds, $bookedData['slot_ids']);


    if (!empty($availableTypeTwoIds)) {
      $slot = Slot::with('seats')
        ->whereIn('id', $availableTypeTwoIds)
        ->where('slot_unique_id', $slotUniqueId)
        ->where('ticket_id', $ticketId)
        ->where('event_id', $eventId)
        ->where('is_deactive', 0)
        ->where('type', 2)
        ->orderByRaw('CASE WHEN price = 0 THEN 1 ELSE 0 END, price ASC') 
        ->first();
    }


    // --- TYPE 1 SLOT HANDLING ---
    if (!$slot) {
      $seatIds = SlotSeats::whereHas('slot', function ($q) use ($slotUniqueId, $ticketId, $eventId) {
        $q->where([
          'slot_unique_id' => $slotUniqueId,
          'ticket_id'      => $ticketId,
          'event_id'       => $eventId,
          'type'           => 1,
          'is_deactive'    => 0,
        ]);
      })
        ->where('is_deactive',0)
        ->pluck('id')
        ->toArray();

      $availableSeatIds = array_diff($seatIds, $bookedData['seat_ids']);

      if (!empty($availableSeatIds)) {
        $slot = Slot::with(['seats' => function ($q) use ($availableSeatIds) {
          $q->whereIn('id', $availableSeatIds)
            ->where('is_deactive', 0)
            ->where('price', '>', 0)
            ->orderBy('price', 'asc');
        }])
          ->where([
            'slot_unique_id' => $slotUniqueId,
            'ticket_id'      => $ticketId,
            'event_id'       => $eventId,
            'type'           => 1,
            'is_deactive'    => 0,
          ])
          ->orderBy('pos_x')
          ->first();
        if ($slot && $slot->seats->isNotEmpty()) {
          $slot->price = $slot->seats->first()->price;
        }
      }
    }

    return [
      'available_seat' => (bool) $slot,
      'price'          => $slot->price ?? 0.00,
    ];
  }

  public function checkBookingAndDeactiveSlotSeat($selectedSlotSeat, $event_id)
  {

    $check = false;
    $bookedAndDeactiveSlotSeat =  $this->getBookingDeactiveData($event_id);

    foreach ($selectedSlotSeat as $slotSeat) {
      $check = in_array($slotSeat['slot_id'], $bookedAndDeactiveSlotSeat['slot_is_deactive_ids']);
      if ($check == true) {
        $check = true;
        break;
      }
      $seatIdsArr = array_column($slotSeat['seats'], 'seat_id');
      $machSeatArr = array_intersect($bookedAndDeactiveSlotSeat['seat_ids'], $seatIdsArr);
      if (count($machSeatArr) > 0) {
        $check = true;
        break;
      }
    }
    return $check;

  }

  public function getBookingDeactiveData(int $eventId)
  {
    $slotUniqueIds = [];
    $slotIds = [];
    $seatIds = [];
    $bookedTickets = Booking::where('event_id', $eventId)
      ->get();
    foreach ($bookedTickets as $ticket) {
      if (!empty($ticket->variation)) {
        $data = json_decode($ticket->variation, true);
        $slotIds[] = array_column($data, 'slot_id');
        $seatIds[] = array_column($data, 'seat_id');
        $slotUniqueIds[] = array_column($data, 'slot_unique_id');
      }
    }
    $slotIds_2 = array_values(array_unique(array_merge(...$slotIds)));
    $seatIds_2 = array_values(array_unique(array_merge(...$seatIds)));
    $slotUniqueIds_2 = array_values(array_unique(array_merge(...$slotUniqueIds)));
    //deactive slot , $seats
    $deactiveSlots = [];
    $deactiveSeats = [];
    $slots = Slot::where('event_id', $eventId)->select('id', 'is_deactive')->get();
    foreach ($slots as $slot) {
      //slot ids
      if ($slot->is_deactive == 1) {
        $deactiveSlots[] = $slot->id;
      }
      if ($slot->seats()->count() == $slot->seats()->where('is_deactive', 1)->count()) {
        $deactiveSlots[] = $slot->id;
      }


      //seat ids
      foreach ($slot->seats()->get() as $seat) {
        if ($seat->is_deactive == 1) {
          $deactiveSeats[] = $seat->id;
        }
      }
    }

    $seatIds_3 = array_values(array_unique(array_merge($deactiveSeats, $seatIds_2)));

    return [
      'slot_ids' => $slotIds_2,
      'seat_ids' => $seatIds_3,
      'slot_unique_ids' => $slotUniqueIds_2,
      'slot_is_deactive_ids' => $deactiveSlots,
    ];
  }


}
