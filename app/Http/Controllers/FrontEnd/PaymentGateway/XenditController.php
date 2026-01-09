<?php

namespace App\Http\Controllers\FrontEnd\PaymentGateway;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\Event\BookingController;
use App\Jobs\BookingInvoiceJob;
use App\Models\BasicSettings\Basic;
use App\Models\Earning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class XenditController extends Controller
{
  public function makePayment(Request $request, $event_id)
  {
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Booking Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    $currencyInfo = $this->getCurrencyInfo();
    $allowed_currency = array('IDR', 'PHP', 'USD', 'SGD', 'MYR');
    if (!in_array($currencyInfo->base_currency_text, $allowed_currency)) {
      return back()->with(['alert-type' => 'error', 'message' => 'Invalid Currency.']);
    }

    $rules = [
      'fname' => 'required',
      'lname' => 'required',
      'email' => 'required',
      'phone' => 'required',
      'country' => 'required',
      'address' => 'required',
      'gateway' => 'required',
    ];

    $message = [];

    $message['fname.required'] = 'The first name feild is required';
    $message['lname.required'] = 'The last name feild is required';
    $message['gateway.required'] = 'The payment gateway feild is required';
    $request->validate($rules, $message);

    $total = Session::get('grand_total');
    $quantity = Session::get('quantity');
    $discount = Session::get('discount');

    //tax and commission end
    $basicSetting = Basic::select('commission')->first();

    $tax_amount = Session::get('tax');
    $commission_amount = ($total * $basicSetting->commission) / 100;

    $total_early_bird_dicount = Session::get('total_early_bird_dicount');
    // changing the currency before redirect to PayPal


    $arrData = array(
      'event_id' => $event_id,
      'price' => $total,
      'tax' => $tax_amount,
      'commission' => $commission_amount,
      'quantity' => $quantity,
      'discount' => $discount,
      'total_early_bird_dicount' => $total_early_bird_dicount,
      'currencyText' => $currencyInfo->base_currency_text,
      'currencyTextPosition' => $currencyInfo->base_currency_text_position,
      'currencySymbol' => $currencyInfo->base_currency_symbol,
      'currencySymbolPosition' => $currencyInfo->base_currency_symbol_position,
      'fname' => $request->fname,
      'lname' => $request->lname,
      'email' => $request->email,
      'phone' => $request->phone,
      'country' => $request->country,
      'state' => $request->state,
      'city' => $request->city,
      'zip_code' => $request->zip_code,
      'address' => $request->address,
      'paymentMethod' => 'Xendit',
      'gatewayType' => 'online',
      'paymentStatus' => 'completed',
    );

    $payable_amount = round($total + $tax_amount, 2);
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Booking End ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Payment Gateway Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    $external_id = Str::random(10);
    $secret_key = config('xendit.key_auth');

    $data_request = Http::withHeaders([
      'Authorization' => 'Basic ' . $secret_key,
    ])->post('https://api.xendit.co/v2/invoices', [
      'external_id' => $external_id,
      'amount' => $payable_amount,
      'currency' => $currencyInfo->base_currency_text,
      'success_redirect_url' => route('event_booking.xindit.notify')
    ]);
    $response = $data_request->object();

    if (isset($response->error_code) && $response->error_code == 'UNSUPPORTED_CURRENCY') {
      return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' =>  __('Invalid Currency')]);
    }

    $response = json_decode(json_encode($response), true);
    if (!empty($response['success_redirect_url'])) {
      $request->session()->put('event_id', $event_id);
      $request->session()->put('arrData', $arrData);
      $request->session()->put('xendit_id', $response['id']);
      $request->session()->put('secret_key', config('xendit.key_auth'));
      $request->session()->put('xendit_payment_type', 'event');

      return redirect($response['invoice_url']);
    } else {
      return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => $response['message']]);
    }
  }

  public function notify(Request $request)
  {

    $xendit_id = Session::get('xendit_id');
    $secret_key = config('xendit.key_auth');
    $response = Http::withHeaders([
      'Authorization' => 'Basic ' . $secret_key,
    ])->get("https://api.xendit.co/v2/invoices/{$xendit_id}");

    if ($response->failed()) {
      return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => 'Failed to verify payment']);
    }
    $payment = $response->object();

    if (isset($payment->status) && in_array($payment->status, ['PAID', 'SETTLED'])) {
      // get the information from session
      $event_id = Session::get('event_id');
      $arrData = Session::get('arrData');
      $enrol = new BookingController();

      // store the course enrolment information in database
      $bookingInfo = $enrol->storeData($arrData);

      $ticket = DB::table('basic_settings')->select('how_ticket_will_be_send')->first();

      if ($ticket->how_ticket_will_be_send == 'instant') {
        // generate an invoice in pdf format
        $invoice = $enrol->generateInvoice($bookingInfo, $bookingInfo->event_id);

        //unlink qr code
        if ($bookingInfo->variation != null) {
          //generate qr code for without wise ticket
          $variations = json_decode($bookingInfo->variation, true);
          foreach ($variations as $variation) {
            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '__' . $variation['unique_id'] . '.svg');
          }
        } else {
          //generate qr code for without wise ticket
          for ($i = 1; $i <= $bookingInfo->quantity; $i++) {
            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '__' . $i .  '.svg');
          }
        }

        // then, update the invoice field info in database
        $bookingInfo->invoice = $invoice;
        $bookingInfo->save();

        // send a mail to the customer with the invoice
        $enrol->sendMail($bookingInfo);
      } else {
        BookingInvoiceJob::dispatch($bookingInfo->id)->delay(now()->addSeconds(10));
      }

      //add blance to admin revinue
      $earning = Earning::first();
      $earning->total_revenue = $earning->total_revenue + $arrData['price'] + $bookingInfo->tax;
      if ($bookingInfo['organizer_id'] != null) {
        $earning->total_earning = $earning->total_earning + ($bookingInfo->tax + $bookingInfo->commission);
      } else {
        $earning->total_earning = $earning->total_earning + $arrData['price'] + $bookingInfo->tax;
      }
      $earning->save();

      //storeTransaction
      $bookingInfo['paymentStatus'] = 1;
      $bookingInfo['transcation_type'] = 1;

      storeTranscation($bookingInfo);

      //store amount to organizer
      $organizerData['organizer_id'] = $bookingInfo['organizer_id'];
      $organizerData['price'] = $arrData['price'];
      $organizerData['tax'] = $bookingInfo->tax;
      $organizerData['commission'] = $bookingInfo->commission;
      storeOrganizer($organizerData);

      // remove all session data
      Session::forget('event_id');
      Session::forget('selTickets');
      Session::forget('arrData');
      Session::forget('paymentId');
      Session::forget('discount');
      Session::forget('xendit_id');
      Session::forget('secret_key');
      Session::forget('xendit_payment_type');
      return redirect()->route('event_booking.complete', ['id' => $event_id, 'booking_id' => $bookingInfo->id]);
    }

    return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => 'Payment failed']);

  }
}
