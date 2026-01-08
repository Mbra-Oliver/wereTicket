<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event\Booking;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrganizerScannerController extends Controller
{

  /* ********************************
     * Submit login for authentication
     * ********************************/
  public function loginSubmit(Request $request)
  {
    $rules = [
      'username' => 'required',
      'password' => 'required',
      'device_name' => 'nullable',
    ];
    $messages = [];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    // Attempt login manually using credentials
    $organizer = Organizer::where('username', $request->username)->first();

    if (!$organizer || !Hash::check($request->password, $organizer->password)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid credentials'
      ], 401);
    }

    if (is_null($organizer->email_verified_at)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Please verify your email address.'
      ], 403);
    }

    if ($organizer->status == 0) {
      return response()->json([
        'status' => 'error',
        'message' => 'Sorry, your account has been deactivated.'
      ], 403);
    }
    // Delete old tokens and create new one
    $organizer->tokens()->where('name', $request->device_name ?? 'unknown-device')->delete();

    $token = $organizer->createToken($request->device_name ?? 'unknown-device')->plainTextToken;

    // Add full photo URL if exists
    Auth::guard('organizer_sanctum')->user($organizer);

    $organizer->photo = !empty($organizer->photo) ?  asset('assets/admin/img/organizer-photo/' . $organizer->photo)  : asset('assets/admin/img/blank_user.jpg');

    return response()->json([
      'status' => 'success',
      'organizer' => $organizer,
      'token' => $token
    ], 200);
  }
  //check qr-code
  public function check_qrcode(Request $request)
  {
    $organizer_id = Auth::guard('organizer_sanctum')->user()->id;

    if (str_contains($request->booking_id, '__')) {
      $ids = explode('__', $request->booking_id);
      $booking_id = $ids[0];
      $unique_id = $ids[1];
      $check = Booking::where([['booking_id', $booking_id]])->first();
      if ($check) {
        if ($check->organizer_id == $organizer_id) {
          // check payment status completed or not
          if ($check->paymentStatus == 'completed' || $check->paymentStatus == 'free') {
            //check scanned_tickets column empty or not
            if (is_null($check->scanned_tickets)) {
              $scannedTicketArr = [
                $unique_id
              ];
              $check->scanned_tickets = json_encode($scannedTicketArr);
              $check->save();
              return response()->json(
                ['alert_type' => 'success',
                 'message' => 'Verified',
                'booking_id' => $request->booking_id
              ]);
            } else {
              //ticket random id will be insert
              $scannedTicketArr = json_decode($check->scanned_tickets, true);
              if (!in_array($unique_id, $scannedTicketArr)) {
                array_push($scannedTicketArr, $unique_id);
                $check->scanned_tickets = json_encode($scannedTicketArr);
                $check->save();
                return response()->json([
                  'alert_type' => 'success',
                  'message' => 'Verified',
                  'booking_id' => $request->booking_id
                ]);
              } else {

                return response()->json([
                  'alert_type' => 'error',
                  'message' => 'Already Scanned',
                  'booking_id' => $request->booking_id
                ]);
              }
            }
          } elseif ($check->paymentStatus == 'pending') {
            return response()->json([
              'alert_type' => 'error',
              'message' => 'Payment incomplete',
              'booking_id' => $request->booking_id
            ]);
          } elseif ($check->paymentStatus == 'rejected') {
            return response()->json([
              'alert_type' => 'error',
              'message' => 'Payment Rejected',
              'booking_id' => $request->booking_id
            ]);
          }
        } else {
          return response()->json([
            'alert_type' => 'error',
            'message' => 'you do not have permission'
          ]);
        }
      } else {
        return response()->json([
          'alert_type' => 'error',
          'message' => 'Unverified'
        ]);
      }
    } else {
      return response()->json([
        'alert_type' => 'error',
         'message' => 'Unverified'
      ]);
    }

  }

  public function logoutSubmit(Request $request)
  {

    $request->user()->currentAccessToken()->delete();
    return response()->json([
      'status' => 'success',
      'message' => 'Logout successfully'
    ], 200);
  }


  public function authentication_fail()
  {
    return response()->json([
      'success' => false,
      'message' => 'Unauthenticated.'
    ], 401);
  }
}
