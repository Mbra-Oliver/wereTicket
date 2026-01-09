<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventCity extends Model
{
  use HasFactory;
  protected  $guarded = [];

  public function country()
  {
    return $this->belongsTo(EventCountry::class);
  }

  public function state()
  {
    return $this->belongsTo(EventState::class);
  }
}
