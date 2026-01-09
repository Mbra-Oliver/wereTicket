<?php

namespace App\Models\Event;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Ticket extends Model
{
  use HasFactory;

  protected $fillable = [
    'event_id',
    'event_type',
    'title',
    'ticket_available_type',
    'ticket_available',
    'max_ticket_buy_type',
    'max_buy_ticket',
    'description',
    'pricing_type',
    'price',
    'f_price',
    'early_bird_discount_type',
    'early_bird_discount',
    'early_bird_discount_amount',
            'free_tickete_slot_enable',

    'early_bird_discount_date',
    'early_bird_discount_time',
    'variations',
    'trans_vars',

    'normal_ticket_slot_enable',
    'normal_ticket_slot_unique_id',

    'free_tickete_slot_enable',
    'free_tickete_slot_unique_id',

    'slot_seat_min_price',
  ];

  public function event()
  {
    return $this->belongsTo(Event::class,'event_id','id');
  }

    // when ticket delete
  protected static function boot()
  {
    parent::boot();
    static::deleting(function ($ticket) {
      $slot_unique_ids = [];
      $variations = json_decode($ticket->variations,true);
      if(!empty($variations)){
        $slot_unique_ids = array_merge($slot_unique_ids, array_column($variations, 'slot_unique_id'));
      }
      if(!empty($ticket->slot_unique_id)){
        $slot_unique_ids[] = $ticket->slot_unique_id;
      }
      $slot_unique_ids = array_unique($slot_unique_ids);
      foreach($slot_unique_ids as $slot_unique){
        $slot = Slot::where('slot_unique_id',$slot_unique)->first();
        if(!is_null($slot)){
          $slot->delete();
        }
        $slotImage = SlotImage::where('slot_unique_id',$slot_unique)->first();
        if(!is_null($slotImage)){
          @unlink(public_path('assets/admin/img/map-image/' . $slotImage->image));
          $slotImage->delete();
        }
      }
    });
  }
}
