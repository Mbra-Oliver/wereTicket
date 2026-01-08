<?php

namespace App\Traits;

use stdClass;

trait ApiFormatTrait
{
  /**
   * format oragnizer data
   */
  protected function format_organizer_data($data, $type)
  {
    $obj = new stdClass();
    $obj->id = $data->id ?? null;
    $obj->photo = $type == "admin" ? asset('assets/admin/img/admins/' . ($data->image ?? "")) : asset('assets/admin/img/organizer-photo/' . ($data->photo ?? ""));

    $obj->phone = $type == 'admin' ?  $data->phone : $data->phone;
    $obj->email = $type == 'admin' ?  $data->email : $data->email;
    $obj->username = $type == 'admin' ?  $data->username : $data->username;
    $obj->status = $type == 'admin' ?  1 : $data->status;
    $obj->facebook = $type == 'admin' ?  null : $data->facebook;
    $obj->twitter =  $type == 'admin' ?  null : $data->twitter;
    $obj->linkedin = $type == 'admin' ?  null : $data->linkedin;
    $obj->organizer_name =  $type == 'admin' ? ($data->first_name ?? "") . ' ' . ($data->last_name ?? "") : ($data->organizer_name ?? "");
    $obj->country = $type == 'admin' ?  null : $data->country;
    $obj->city =  $type == 'admin' ?  null : $data->city;
    $obj->state = $type == 'admin' ?  null : $data->state;
    $obj->address = $type == 'admin' ?  $data->address : $data->address;
    $obj->zip_code = $type == 'admin' ?  null : $data->zip_code;
    $obj->designation = $type == 'admin' ?  null : $data->designation;
    $obj->details = $type == 'admin' ?  $data->details : $data->details;
    $obj->user_type = $type;
    return $obj;
  }

  /**
   * foramt sender data use this support ticket
   */
  protected function format_sender_data($data, $type)
  {
    $obj = new stdClass();
    $obj->role_id = $type == 'admin' ? null : 'customer';
    $obj->full_name =  $type == 'admin' ? ($data->first_name ?? "") . ' ' . ($data->last_name ?? "") : ($data->fname ?? "") . ' ' . ($data->lname ?? "");
    $obj->email = $type == 'admin' ?  $data->email : $data->email;
    $obj->username = $type == 'admin' ?  $data->username : $data->username;

    $obj->photo = asset($type == "admin" ? (!empty($data->image) ? "assets/admin/img/admins/{$data->image}" : "assets/admin/img/blank_user.jpg") : (!empty($data->photo) ? "assets/admin/img/customer-profile/{$data->photo}" : "assets/admin/img/blank_user.jpg"));


    $obj->phone = $type == 'admin' ?  $data->phone : $data->phone;
    $obj->address = $type == 'admin' ?  null : $data->address;
    $obj->country = $type == 'admin' ?  null : $data->country;
    $obj->city =  $type == 'admin' ?  null : $data->city;
    $obj->state = $type == 'admin' ?  null : $data->state;
    $obj->zip_code = $type == 'admin' ?  null : $data->zip_code;
    $obj->status = $type == 'admin' ?  1 : $data->status;
    $obj->user_type = $type;
    return $obj;
  }

  /**
   * fformat oragnizer data this is use organizer index
   */
  protected function format_organizer_data_2($data)
  {
    $obj = new stdClass();
    $obj->id = $data->id;
    $obj->photo = !empty($data->photo) ? asset('assets/admin/img/organizer-photo/' . $data->photo) : asset('assets/front/images/user.png');

    $obj->phone =  $data->phone ?? null;
    $obj->email = $data->email ?? null;
    $obj->username = $data->username ?? null;
    $obj->status = $data->status ?? 1;
    $obj->facebook = $data->facebook ?? null;
    $obj->twitter =   $data->twitter ?? null;
    $obj->linkedin = $data->linkedin ?? null;
    $obj->organizer_name =  $data->name ?? null;
    $obj->country = $data->country ?? null;
    $obj->city =  $data->city ?? null;
    $obj->state = $data->state ?? null;
    $obj->address = $data->address ?? null;
    $obj->zip_code = $data->zip_code ?? null;
    $obj->designation = $data->designation ?? null;
    $obj->user_type = 'organizer';
    return $obj;
  }
}
