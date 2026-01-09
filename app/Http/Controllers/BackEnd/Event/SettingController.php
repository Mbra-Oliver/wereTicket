<?php

namespace App\Http\Controllers\BackEnd\Event;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
  public function index()
  {
    $content = DB::table('basic_settings')->select('event_country_status', 'event_state_status')->first();
    return view('backend.event.specification.settings', compact('content'));
  }

  public function update(Request $request)
  {
    DB::table('basic_settings')->update([
      'uniqid' => 12345,
      'event_country_status' => $request->event_country_status ?? 0,
      'event_state_status' => $request->event_state_status ?? 0,
    ]);

    session()->flash('success', 'Property Settings Updated Successfully!');
    return back();
  }
}
