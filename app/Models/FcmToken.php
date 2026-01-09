<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
  use HasFactory;

  protected $fillable = [
    'token',
    'booking_id',
    'user_id',
    'platform',
    'message_title',
    'message_description'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
