<?php

namespace App\Http\Controllers\FrontEnd\PaymentGateway;

use App\Helpers\CinetPay;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\Event\BookingController;
use App\Http\Helpers\CinetPay as HelpersCinetPay;
use App\Models\BasicSettings\Basic;
use App\Models\Earning;
use App\Models\Event;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CinetPayController extends Controller
{
    /*
     * Perfect Money Gateway
     */
    public static function makePayment(Request $request, $event_id)
    {
        try {
            /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            ~~~~~~~~~~~~~~~~~ Booking Info ~~~~~~~~~~~~~~
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
            $currencyInfo = Basic::select('base_currency_text')->first();

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
                'paymentMethod' => 'Cinetpay',
                'gatewayType' => 'online',
                'paymentStatus' => 'completed',
            );


            $payable_amount = round($total + $tax_amount, 2);
            /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            ~~~~~~~~~~~~~~~~~ Booking End ~~~~~~~~~~~~~~
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

            /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            ~~~~~~ Payment Gateway Init Start ~~~~~~~~~~
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/

            $randomNo = substr(uniqid(), 0, 8);
            $websiteInfo = Basic::select('website_title')->first();
            $cinetpay_money = OnlineGateway::where('keyword', 'cinetpay')->first();
            $info = json_decode($cinetpay_money->information, true);
            $val['SITE_ID'] = $info['site_id'];
            $val['API_KEY'] = $info['api_key'];;
            $val['PAYEE_NAME'] = $websiteInfo->website_title;
            $val['PAYMENT_ID'] = "$randomNo"; //random id
            $val['PAYMENT_AMOUNT'] = $payable_amount;
            $val['PAYMENT_UNITS'] = "$currencyInfo->base_currency_text";

            $val['STATUS_URL'] = route('event_booking.perfect-money.notify');
            $val['PAYMENT_URL'] = route('event_booking.perfect-money.notify');
            $val['PAYMENT_URL_METHOD'] = 'GET';
            $val['NOPAYMENT_URL'] = route('event_booking.perfect-money.cancel');
            $val['NOPAYMENT_URL_METHOD'] = 'GET';
            $val['SUGGESTED_MEMO'] = "$request->fname " . " " . "$request->lname";
            $val['BAGGAGE_FIELDS'] = 'IDENT';



            //Obtenir les infos de l'evenement

            $product = Event::find($event_id);

            //Generer les donnees pour le paiement


            $dataToSendToCinetPay = [
                'transaction_id' => $randomNo,
                'amount' => 100,
                'currency' => 'XOF',
                'customer_name' => $request->fname,
                'customer_surname' => $request->lname,
                'customer_email' => $request->email,
                'customer_phone_number' => $request->phone,
                'customer_address' => $request->address,
                'customer_city' => $request->address,
                'customer_state' => $request->address,
                'customer_country' => 'CI',
                'invoice_data' => [
                    'id' => $event_id,
                    'name' => $product->event_type,
                    'price' => $payable_amount
                ],
                'description' => 'achat de ticket d\'évenement' . $product->name,
                'notify_url' => $val['PAYMENT_URL'],
                'return_url' => $val['PAYMENT_URL'],
                'channels' => 'MOBILE_MONEY',
                'metadata' => '',
                'customer_zip_code' => '00225'
            ];

            $initCinetPay = new HelpersCinetPay($info['site_id'], $info['api_key']);
            //Enregistrement de paiement

            $request->session()->put('payment_id', $randomNo);
            $request->session()->put('event_id', $event_id);
            $request->session()->put('arrData', $arrData);


            try {


                $resultCinetPayInit = $initCinetPay->generatePaymentLink($dataToSendToCinetPay);

                if ($resultCinetPayInit['code'] === "201") {
                    //Afficher lien de paiement
                    $PaymentLink = $resultCinetPayInit['data']['payment_url'];
                    return redirect()->to($PaymentLink);
                } else {
                    //Afficher message d'erreur
                    throw new \Exception('Payment initialization failed.');
                }
            } catch (\Exception $e) {
                dd($e);
                return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => 'Payment Failed: ' . $e->getMessage()]);
            }

            /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            ~~~~~~ Payment Gateway Init End ~~~~~~~~~~
            ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        } catch (\Exception $e) {
            dd($e);
            return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => 'Payment Failed: ' . $e->getMessage()]);
        }
    }
    public function notify(Request $request)
    {
        // get the information from session
        $event_id = Session::get('event_id');
        $arrData = Session::get('arrData');
        $final_amount = $arrData['price'] + $arrData['tax'];

        $perfect_money = OnlineGateway::where('keyword', 'cinetpay')->first();
        $perfectMoneyInfo = json_decode($perfect_money->information, true);
        $currencyInfo = Basic::select('base_currency_text')->first();

        $amo = $request['PAYMENT_AMOUNT'];
        $unit = $request['PAYMENT_UNITS'];
        $track = $request['PAYMENT_ID'];
        $id = Session::get('payment_id');
        if ($request->PAYEE_ACCOUNT == $perfectMoneyInfo['perfect_money_wallet_id'] && $unit == $currencyInfo->base_currency_text && $track == $id && $amo == round($final_amount, 2)) {
            //success payment and save data into database
            $booking = new BookingController();

            // store the course enrolment information in database
            $bookingInfo = $booking->storeData($arrData);
            // generate an invoice in pdf format
            $invoice = $booking->generateInvoice($bookingInfo, $event_id);
            //unlink qr code
            @unlink(public_path('assets/admin/qrcodes/') . $bookingInfo->booking_id . '.svg');
            //end unlink qr code

            // then, update the invoice field info in database
            $bookingInfo->update(['invoice' => $invoice]);

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

            // send a mail to the customer with the invoice
            $booking->sendMail($bookingInfo);

            // remove all session data
            Session::forget('event_id');
            Session::forget('selTickets');
            Session::forget('arrData');
            Session::forget('paymentId');
            Session::forget('discount');
            Session::forget('payment_id');
            return redirect()->route('event_booking.complete', ['id' => $event_id, 'booking_id' => $bookingInfo->id]);
        } else {
            return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => 'Payment Failed']);
        }
    }

    public function cancel()
    {
        return redirect()->route('check-out')->with(['alert-type' => 'error', 'message' => 'Payment Canceled']);
    }
}
