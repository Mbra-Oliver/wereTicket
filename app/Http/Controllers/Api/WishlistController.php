<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\PageHeading;
use App\Models\Event\Wishlist;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
  /* ***************************
     * wishlist
     * ***************************/
  public function index(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_wishlist_page_title')->first();

    $data['wishlists'] = Wishlist::query()
      ->join('events', 'wishlists.event_id', 'events.id')
      ->join('event_contents', 'event_contents.event_id', 'wishlists.event_id')
      ->where('event_contents.language_id', $language->id)
      ->where('customer_id', $customer->id)
      ->select('wishlists.id', 'wishlists.event_id', 'event_contents.title', 'events.thumbnail as image')
      ->get()->map(function($item){
        $item->image = asset('assets/admin/img/event/thumbnail/'.$item->image);
        return $item;
      });

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /* ***************************
     * Add to wishlist
     * ***************************/
  public function store(Request $request)
  {

    //validation rules
    $rules = [
      'event_id' => 'required',
      'customer_id' => 'required',
    ];

    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    //validation end

    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $check = Wishlist::where([['event_id', $request->event_id], ['customer_id', $customer->id]])->first();

    if (!empty($check)) {
      return response()->json([
        'success' => false,
        'message' => __('This event is already in your wishlist.'),
      ]);
    } else {
      $add = new Wishlist();
      $add->event_id = $request->event_id;
      $add->customer_id = $customer->id;
      $add->save();
      return response()->json([
        'success' => true,
        'message' => __('Event added to wishlist successfully.'),
      ]);
    }
  }

  /* ***************************
     * Delete from wishlist
     * ***************************/
  public function delete(Request $request)
  {
    // Authenticate customer
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $wishlist = Wishlist::where([['customer_id', $customer->id], ['id', $request->id]])->first();
    if ($wishlist) {
      $wishlist->delete();
      return response()->json([
        'success' => true,
        'message' => 'The wishlist has been deleted successfully'
      ]);
    } else {
      return response()->json([
        'success' => false,
        'message' => 'wishlist not found'
      ]);
    }
  }
}
