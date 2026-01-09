<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventContent extends Model
{
  use HasFactory;

  protected $fillable = [
    'event_id',
    'event_category_id',
    'title',
    'address',
    'country',
    'state',
    'city',
    'zip_code',
    'description',
    'meta_keywords',
    'meta_description',
    'google_calendar_id',
    'refund_policy',
    'country_id',
    'state_id',
    'city_id',
  ];

  public function tickets()
  {
    return $this->hasMany(Ticket::class, 'event_id', 'event_id');
  }
}
