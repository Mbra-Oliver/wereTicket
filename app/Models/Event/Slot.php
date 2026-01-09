<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;
    protected $appends = ['filtered_seats'];
    protected $fillable = [
      'event_id',
      'ticket_id',
      'slot_enable',
      'slot_unique_id',
      'pos_x',
      'pos_y',
      'rotate',
      'background_color',
      'width',
      'height',
      'round',
      'name',
      'type',
      'number_of_seat',
      'price',
      'border_color',
      'font_size',
      'is_deactive',
      'is_booked',
       'pricing_type'
    ];

  public function seats()
  {
    return $this->hasMany(SlotSeats::class, 'slot_id', 'id');
  }

  public function getFilteredSeatsAttribute()
  {
    $seats = $this->getRelationValue('seats');
    if ($this->type == 2) {
      return $seats->where('is_deactive', 0)->values();
    }
    return $seats;
  }
  
  protected static function boot()
  {
    parent::boot();
    static::deleting(function ($slot) {
      $slot->seats()->delete();
    });
  }
}
