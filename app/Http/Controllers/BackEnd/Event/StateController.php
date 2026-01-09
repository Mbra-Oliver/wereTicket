<?php

namespace App\Http\Controllers\BackEnd\Event;

use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Event\EventState;
use App\Models\Event\EventCountry;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
  public function index(Request $request)
  {
    $lang = Language::where('code', $request->language)->first();
    $data['langs'] = Language::all();

    $data['states'] = EventState::with(['country' => function ($q) use ($lang) {
      $q->where('language_id', $lang->id);
    }])
      ->orderBy('created_at', 'desc')
      ->where('language_id', $lang->id)
      ->paginate(10);

    $data['countries'] = DB::table('event_countries')->where('language_id', $lang->id)->get();

    return view('backend.event.specification.state.index', $data);
  }

  public function edit($id, Request $request)
  {
    $data['state'] = EventState::findOrFail($id);

    $data['selectedCountry'] = EventCountry::where('id', $data['state']->country_id)->select('id', 'name')->first();

    return view('backend.event.specification.state.edit', $data);
  }

  public function store(Request $request)
  {
    $rules = [
      'language_id' => 'required',
      'name' => 'required|unique:event_countries,name',
      'status' => 'required',
      'serial_number' => 'required|numeric|min:0',
    ];
    $event_country_status = DB::table('basic_settings')
      ->pluck('event_country_status')
      ->first();

    if ($event_country_status == 1) {
      $rules['country_id'] = 'required';
    }

    $messages = [];
    $messages['language_id.required'] = __('The language field is required.');
    $messages['country_id.required'] = __('The country field is required.');
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //store data
    $state = new EventState();
    $state->language_id = $request->language_id;
    $state->country_id = $request->country_id;
    $state->name = $request->name;
    $state->slug = createSlug($request->name);
    $state->status = $request->status;
    $state->serial_number = $request->serial_number;
    $state->save();

    session()->flash('success', 'Added Successfully');
    return response()->json(['status' => 'success'], 200);
  }

  public function update(Request $request)
  {
    $rules = [
      'name' => 'required|unique:event_countries,name,' . $request->id,
      'status' => 'required',
      'serial_number' => 'required|numeric|min:0',
    ];
    $event_country_status = DB::table('basic_settings')
      ->pluck('event_country_status')
      ->first();

    if ($event_country_status == 1) {
      $rules['country_id'] = 'required';
    }
    $messages = [];
    $messages['country_id.required'] = __('The country field is required.');
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //store data
    $state = EventState::findOrFail($request->id);
    $state->language_id = $state->language_id;
    $state->country_id = $request->country_id;
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
    $state = EventState::where('id', $id)->first();
    $state->delete();
    return redirect()->back()->with('success', 'Deleted Successfully');
  }

  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $state = EventState::where('id', $id)->first();
      $state->delete();
    }
    session()->flash('success', 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }
}
