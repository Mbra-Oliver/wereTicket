<?php

namespace App\Http\Controllers\BackEnd\Event;

use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\Event\EventCountry;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
  public function index(Request $request)
  {
    $lang = Language::where('code', $request->language)->first();
    $data['langs'] = Language::all();
    $data['countries'] = DB::table('event_countries')
      ->where('language_id', $lang->id)
      ->orderBy('created_at', 'desc')
      ->paginate(10);

    return view('backend.event.specification.country.index', $data);
  }

  public function store(Request $request)
  {
    $rules = [
      'language_id' => 'required',
      'name' => 'required',
      'status' => 'required',
      'serial_number' => 'required|numeric|min:0',
    ];
    $messages = [];
    $messages['language_id.required'] = __('The language field is required.');
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //store data
    $country = new EventCountry();
    $country->language_id = $request->language_id;
    $country->name = $request->name;
    $country->slug = createSlug($request->name);
    $country->status = $request->status;
    $country->serial_number = $request->serial_number;
    $country->save();

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
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    //store data
    $country = EventCountry::findOrFail($request->id);
    $country->language_id = $country->language_id;
    $country->name = $request->name;
    $country->slug = createSlug($request->name);
    $country->status = $request->status;
    $country->serial_number = $request->serial_number;
    $country->save();

    session()->flash('success', 'Updated Successfully');
    return response()->json(['status' => 'success'], 200);
  }

  public function destroy($id, Request $request)
  {
    $country = EventCountry::where('id', $id)->first();
    $country->delete();
    return redirect()->back()->with('success', 'Deleted Successfully');
  }

  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $country = EventCountry::where('id', $id)->first();
      $country->delete();
    }
    session()->flash('success', 'Deleted Successfully');
    return response()->json(['status' => 'success'], 200);
  }
}
