<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotSeats extends Model
{
  use HasFactory;

  public $timestamps = false;
  protected $fillable = [
    'name',
    'type',
    'slot_id',
    'price',
    'is_deactive',
  ];
  public function slot()
  {
    return $this->belongsTo(Slot::class, 'slot_id', 'id');
  }
}
