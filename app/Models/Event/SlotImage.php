<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotImage extends Model
{
    use HasFactory;

    protected $fillable = [
      'event_id',
      'ticket_id',
      'slot_unique_id',
      'image',
    ];

    protected static function boot()
    {
      parent::boot();
      static::deleting(function ($slot_image) {
        if (!empty($slot_image->image)) {
          @unlink(public_path('assets/admin/img/map-image/' . $slot_image->image));
        }
      });
    }
}
