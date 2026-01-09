<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BasicSettings\PageHeading;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Language;
use App\Models\SupportTicket;
use App\Traits\ApiFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class SupportTicketController extends Controller
{
  use ApiFormatTrait;
  /* *****************************
     * Support tickets page
     * *****************************/
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

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('customer_support_ticket_page_title')->first();

    $data['support_tickets'] = SupportTicket::where('user_id', $customer->id)->where('user_type', 'customer')->orderBy('id', 'desc')->get();

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /* *****************************
     * Support ticket create page
     * *****************************/
  public function create(Request $request)
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

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('support_ticket_create_page_title')->first();

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /* ***************************
     * Store support ticket
     * ***************************/
  public function store(Request $request)
  {
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $rules = [
      'subject' => 'required',
      'email' => 'required',
      'description' => 'required',
      'attachment' => $request->hasFile('attachment') ? 'mimes:zip|max:5000' : ''
    ];

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    $in = $request->all();
    $in['user_id'] = $customer->id;
    $in['user_type'] = 'customer';
    $file = $request->file('attachment');
    if ($file) {
      $extension = $file->getClientOriginalExtension();
      $directory = public_path('assets/admin/img/support-ticket/');
      $fileName = uniqid() . '.' . $extension;
      @mkdir($directory, 0775, true);
      $file->move($directory, $fileName);
      $in['attachment'] = $fileName;
    }

    $data['ticket'] = SupportTicket::create($in);
    return response()->json([
      'success' => true,
      'data'    => $data,
      'message' => __("Support Ticket Created Successfully")
    ]);
  }

  /* *****************************
     * Support ticket details page
     * *****************************/
  public function details(Request $request)
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

    $data['page_title'] = PageHeading::where('language_id', $language->id)->pluck('support_ticket_details_page_title')->first();


    $support_ticket = SupportTicket::where('user_id', $customer->id)->where('user_type', 'customer')->where('id', $request->ticket_id)->first();

    if (!$support_ticket) {
      return response()->json([
        'success' => false,
        'message' => 'support ticket not found'
      ]);
    }
    $support_ticket->attachment = asset('assets/admin/img/support-ticket/' . $support_ticket->attachment);
    $data['support_ticket'] = $support_ticket;
    $messages = $support_ticket->messages ?? collect([]);
    $messages->map(function ($message) {
      if ($message->type == 2) {
        $message->sender_type = "admin";
      } elseif ($message->type == 3) {
        $message->sender_type = "organizer";
      } else {
        $message->sender_type = "customer";
      }

      if ($message->type == 2) {
        $sender = Admin::where('id', $message->user_id)->first();
        $message->sender = $this->format_sender_data($sender, 'admin');
      } else {
        $sender = Customer::where('id', $message->user_id)->first();
        $message->sender = $this->format_sender_data($sender, 'customer');
      }
      return $message;
    });

    return response()->json([
      'success' => true,
      'data'    => $data,
    ]);
  }

  /* ************************
     * Reply customer
     * ************************/
  public function reply(Request $request)
  {
    $customer = Auth::guard('sanctum')->user();
    if (!$customer) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $file = $request->file('file');
    $allowedExts = array('zip');
    $rules = [
      'ticket_id' => 'required',
      'reply' => 'required',
      'file' => [
        function ($attribute, $value, $fail) use ($file, $allowedExts) {

          $ext = $file->getClientOriginalExtension();
          if (!in_array($ext, $allowedExts)) {
            return $fail("Only zip file supported");
          }
        },
        'max:5000'
      ],
    ];

    $messages = [
      'file.max' => ' zip file may not be greater than 5 MB',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $input = $request->all();
    $ticket = SupportTicket::where('id', $request->ticket_id)->first();
    if (!$ticket) {
      return response()->json([
        'success' => false,
        'message' => 'support ticket not found'
      ]);
    }

    $input['reply'] = Purifier::clean($request->reply, 'youtube');
    $input['type'] = 1;
    $input['user_id'] = $customer->id;
    $input['support_ticket_id'] = $request->ticket_id;
    if ($request->hasFile('file')) {
      $file = $request->file('file');
      $filename = uniqid() . '.' . $file->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/support-ticket/'), 0775, true);
      $file->move(public_path('assets/admin/img/support-ticket/'), $filename);
      $input['file'] = $filename;
    }

    $data = new Conversation();
    $message = $data->create($input);
    $support_ticket = SupportTicket::where('id', $request->ticket_id)->where('user_id', $customer->id)->first();

    $support_ticket->update([
      'last_message' => Carbon::now()
    ]);

    $data['support_ticket'] = $support_ticket;

    $sender = Customer::where('id', $message->user_id)->first();
    $sender->photo = !is_null($sender->photo) ? asset('assets/admin/img/customer-profile/' . $sender->photo) : asset('assets/front/images/profile.jpg');
    $message->sender = $sender;
    $message->file = !is_null($message->file) ? asset('assets/admin/img/support-ticket/' . $message->file) : null;

    $data['message'] = $message;


    return response()->json([
      'success' => true,
      'data'    => $data,
      'message' => __("Reply message successfully")
    ]);
  }
}
