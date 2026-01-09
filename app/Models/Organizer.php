<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Sanctum\HasApiTokens;

class Organizer extends Model implements AuthenticatableContract
{
  use HasApiTokens, HasFactory, Authenticatable;
  protected $fillable = [
    'photo',
    'email',
    'phone',
    'username',
    'password',
    'status',
    'amount',
    'facebook',
    'twitter',
    'linkedin',
    'email_verified_at',
    'theme_version'
  ];

  //withdraw
  public function withdraws()
  {
    return $this->hasMany(Withdraw::class);
  }

  //organizer info
  public function organizer_info()
  {
    return $this->hasOne(OrganizerInfo::class);
  }
}
