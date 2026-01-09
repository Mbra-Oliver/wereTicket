<?php

namespace App\Http\Controllers\BackEnd\Event;

use App\Models\Event;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Event\EventCity;
use App\Models\Event\EventState;
use App\Models\Event\EventCountry;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
  public function index(Request $request)
  {
    $lang = Language::where('code', $request->language)->first();
    $data['langs'] = Language::all();

    $data['cities'] = EventCity::with([
      'country' => function ($q) use ($lang) {
        $q->where('language_id', $lang->id);
      },
      'state' => function ($q) use ($lang) {
        $q->where('language_id', $lang->id);
      }
    ])
      ->orderBy('created_at', 'desc')
      ->where('language_id', $lang->id)
      ->get();


    $data['countries'] = DB::table('event_countries')->where('language_id', $lang->id)->get();

    return view('backend.event.specification.cities.index', $data);
  }


  public function store(Request $request)
  {
    $rules = [
      'language_id' => 'required',
      'name' => 'required|unique:event_countries,name',
      'status' => 'required',
      'serial_number' => 'required|numeric|min:0',
    ];
    $bs = DB::table('basic_settings')
      ->select('event_country_status', 'event_state_status')->first();

    if ($bs->event_country_status == 1) {
      $rules['country_id'] = 'required';
    }

    $state_exists = EventState::where('country_id', $request->country_id)
      ->where('language_id', $request->language_id)
      ->exists();

    if (($state_exists && $bs->event_state_status == 1) || ($bs->event_country_status == 0 && $bs->event_state_status == 1)) {
      $rules['state_id'] = 'required';
    }

    $messages = [];
    $messages['language_id.required'] = __('The language field is required.');
    $messages['country_id.required'] = __('The country field is required.');
    $messages['state_id.required'] = __('The state field is required.');
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //store data
    $state = new EventCity();
    $state->language_id = $request->language_id;
    $state->country_id = $request->country_id;
    $state->state_id = @$request->state_id;
    $state->name = $request->name;
    $state->slug = createSlug($request->name);
    $state->status = $request->status;
    $state->serial_number = $request->serial_number;
    $state->save();

    session()->flash('success', 'Added Successfully');
    return response()->json(['status' => 'success'], 200);
  }

  public function edit(Request $request)
  {
    $data['city'] = EventCity::findOrFail($request->id);

    $data['selectedCountry'] = EventCountry::where('id', $data['city']->country_id)
      ->select('id', 'name')
      ->first();

    $data['selectedState'] = EventState::where('id', $data['city']->state_id)
      ->select('id', 'name')
      ->first();

    return view('backend.event.specification.cities.edit', $data);
  }


  public function update(Request $request)
  {
    $rules = [
      'name' => 'required|unique:event_countries,name,' . $request->id,
      'status' => 'required',
      'serial_number' => 'required|numeric|min:0',
    ];

    $bs = DB::table('basic_settings')
      ->select('event_country_status', 'event_state_status')->first();

    if ($bs->event_country_status == 1) {
      $rules['country_id'] = 'required';
    }

    $state_exists = EventState::where('country_id', $request->country_id)
      ->exists();

    if (($state_exists && $bs->event_state_status == 1) || ($bs->event_country_status == 0 && $bs->event_state_status == 1)) {
      $rules['state_id'] = 'required';
    }

    $messages = [];
    $messages['country_id.required'] = __('The country field is required.');
    $messages['state_id.required'] = __('The state field is required.');
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //store data
    $state = EventCity::findOrFail($request->id);
    $state->language_id = $state->language_id;
    $state->country_id = $request->country_id;
    $state->state_id = @$request->state_id;
    $state->name = $request->name;
    $state->slug = createSlug($request->name);
    $state->status = $request->status;
    $state->serial_number = $request->serial_number;
    $state->save();

    session()->flash('success', 'Updated Successfully');
    return response()->json(['status' => 'success'], 200);
  }

  public function destroy($id, Request $request)
  {
    $city = EventCity::where('id', $id)->first();
    $city->delete();
    return redirect()->back()->with('success', 'Deleted Successfully');
  }

  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $city = EventCity::where('id', $id)->first();
      $city->delete();
    }
    session()->flash('success', 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }

  /**
   * get cities or states
   */
  public function get_state(Request $request)
  {
    $event_state_status = DB::table('basic_settings')
      ->pluck('event_state_status')->first();

    //if event state status is off then return cities
    if ($event_state_status == 0) {
      $cities =  EventCity::where('country_id', $request->country_id)->exists();
      return response()->json(['cities' => $cities], 200);
    }

    //if event state status is on then return states
    $states =  EventState::where('country_id', $request->country_id)->exists();
    return response()->json(['states' => $states], 200);
  }

  /**
   * get cities
   */
  public function getcities(Request $request)
  {
    $cities =  EventCity::where('state_id', $request->state_id)->select('id', 'name')->get();

    if (count($cities) > 0) {
      return response()->json(['cities' => $cities], 200);
    }

    return response()->json(['cities' => 'no_data_found'], 200);
  }
}
