<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
  public function store(Request $request)
  {
    $rules = ['token' => 'required|string', 'platform' => 'nullable|string'];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }
    FcmToken::updateOrCreate(
      ['token' => $request->token],
      [
        'user_id' => $request->user_id,
        'platform' => $request->platform
      ]
    );

   if(!empty($request->user_id) && !empty($request->token)){
      FcmToken::where('token', $request->token)
      ->whereNull('user_id')
      ->update(['user_id' => $request->user_id]);
   }

    return response()->json(['status' => 'success', 'message' => 'Token saved']);
  }

  public function getNotifications(Request $request)
  {
    $fcm_token = $request->fcm_token;
    $user_id = $request->user_id;
    if(empty($fcm_token) && empty($user_id)){
      return response()->json(
        [
        'status' => 'false',
        'message' => 'fcm_token or user_id field is required.',
        'notifications' =>[]
        ]
      );
    }

    $notifications = FcmToken::where(function ($query) use ($fcm_token, $user_id) {
      $query->where('token', $fcm_token)
        ->orWhere('user_id', $user_id);
    })
    ->where('platform', 'web')
    ->whereNotNull('message_title')
    ->orderBy('created_at', 'desc')
    ->get(['message_title', 'message_description', 'created_at', 'booking_id']);
    return response()->json(['status' => 'success', 'notifications' => $notifications]);
  }
}
