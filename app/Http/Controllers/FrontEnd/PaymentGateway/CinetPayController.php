<?php

namespace App\Http\Controllers\FrontEnd\PaymentGateway;

use App\Helpers\CinetPay as HelpersCinetPay;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\Event\BookingController;
use App\Http\Helpers\CinetPay;
use App\Models\BasicSettings\Basic;
use App\Models\Earning;
use App\Models\Event;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\Booking;
use App\Models\Event\Booking as EventBooking;
use ErrorException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CinetPayController extends Controller
{
    public static function makePayment(Request $request, $event_id)
    {
        try {
            // Validation des données
            $request->validate([
                'fname' => 'required|string',
                'lname' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string',
                'country' => 'required|string',
                'address' => 'required|string',
                'gateway' => 'required|string',
            ]);

            // Récupération des informations
            $currencyInfo = Basic::first(['base_currency_text']);
            $basicSetting = Basic::first(['commission']);
            $product = Event::findOrFail($event_id);

            $total = Session::get('grand_total');
            $tax_amount = Session::get('tax', 0);
            $commission_amount = ($total * $basicSetting->commission) / 100;
            $payable_amount = round($total + $tax_amount, 2);

            // Préparation des données de réservation
            $arrData = [
                'event_id' => $event_id,
                'price' => $total,
                'tax' => $tax_amount,
                'commission' => $commission_amount,
                'quantity' => Session::get('quantity'),
                'discount' => Session::get('discount'),
                'total_early_bird_dicount' => Session::get('total_early_bird_dicount'),
                'currencyText' => $currencyInfo->base_currency_text,
                'fname' => $request->fname,
                'lname' => $request->lname,
                'email' => $request->email,
                'phone' => $request->phone,
                'country' => $request->country,
                'address' => $request->address,
                'paymentMethod' => 'Cinetpay',
                'gatewayType' => 'online',
                'paymentStatus' => '0',
                'currencyTextPosition' => $currencyInfo->base_currency_text_position,
                'currencySymbol' => $currencyInfo->base_currency_symbol,
                'currencySymbolPosition' => $currencyInfo->base_currency_symbol_position,

                'state' => $request->state,
                'city' => $request->city,
                'zip_code' => $request->city,
            ];

            // Configuration CinetPay
            $cinetpay = OnlineGateway::where('keyword', 'cinetpay')->firstOrFail();
            $info = json_decode($cinetpay->information, true);

            $transactionId = uniqid(mt_rand(), true);

            $bookingModel = new BookingController();




            try {
                // store the course enrolment information in database
                $bookingInfo = $bookingModel->storeData($arrData);
            } catch (Exception $error) {
                throw new ErrorException($error->getMessage());
            }

            if (!$bookingInfo) {
                throw new Exception("Échec de l'enregistrement de la réservation");
            }

            // $payable_amount doit être défini avant

            $dataToSend = [
                'transaction_id' => $bookingInfo->booking_id, // Utiliser -> car bookingInfo est un objet
                'amount' => 100, // Corrigé pour utiliser la vraie variable
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
                    'price' => $payable_amount,
                ],
                'description' => 'Achat de ticket pour l\'événement : ' . $product->name,
                'notify_url' => route('event_booking.cinetpay.notify', $bookingInfo->booking_id),
                'return_url' => route('event_booking.cinetpay.return', ['eventId' => $bookingInfo->booking_id]),
                'callback_url' => route('event_booking.cinetpay.notify', $bookingInfo->booking_id),
                'channels' => 'ALL',
                'metadata' => json_encode([
                    'event_id' => $event_id,
                ]),
                'customer_zip_code' => '00225',
            ];


            // Génération du lien de paiement
            $cinetpayClient = new CinetPay($info['site_id'], $info['api_key']);
            $result = $cinetpayClient->generatePaymentLink($dataToSend);

            if (isset($result['code']) && $result['code'] == '201') {
                // Stockage en session
                Session::put('payment_id', $transactionId);
                Session::put('arrData', $arrData);
                Session::put('event_id', $event_id);

                return redirect()->to($result['data']['payment_url']);
            } else {
                Log::error('Erreur Cinetpay init', ['response' => $result]);
                return redirect()->route('check-out')->with('error', 'Échec de l\'initialisation du paiement.');
            }
        } catch (Exception $e) {
            dd($e);
            Log::error('Erreur dans makePayment', ['message' => $e->getMessage()]);
            return redirect()->route('check-out')->with('error',  $e->getMessage() ?? 'Erreur lors de l\'initialisation du paiement.');
        }
    }

    public function notify($bookingId)
    {

        try {


            if (!$bookingId) {
                return response()->json(['message' => 'Transaction ID manquant.'], 400);
            }

            // On suppose que tu as enregistré les transactions ou que tu relies aux sessions
            $arrData = Session::get('arrData');
            if (!$arrData) {
                Log::error('Données de session manquantes pour la transaction', ['transaction_id' => $bookingId]);
                return redirect()->back()->with(['message' => 'Session expirée ou données manquantes.']);
            }

            $bookingController = new BookingController();
            $bookingInfo = Booking::where('booking_id', $bookingId)->first();

            // Vérifier le statut du paiement
            $checkResult = $this->checkPaymentStatus($bookingId);

            if ($checkResult['code'] === '00') {

                $event_id = Session::get('event_id');
                $invoice = $bookingController->generateInvoice($bookingInfo, $event_id);
                $bookingInfo->update([
                    'invoice' => $invoice,
                    'paymentStatus' => 1
                ]);

                $earning = Earning::first();
                $earning->total_revenue += $arrData['price'] + $bookingInfo->tax;
                if ($bookingInfo->organizer_id) {
                    $earning->total_earning += ($bookingInfo->tax + $bookingInfo->commission);
                } else {
                    $earning->total_earning += $arrData['price'] + $bookingInfo->tax;
                }
                $earning->save();

                $bookingInfo['paymentStatus'] = 1;
                $bookingInfo['transaction_type'] = 1; // Correction ici !

                storeTranscation($bookingInfo);

                if ($bookingInfo->organizer_id) {
                    storeOrganizer([
                        'organizer_id' => $bookingInfo->organizer_id,
                        'price' => $arrData['price'],
                        'tax' => $bookingInfo->tax,
                        'commission' => $bookingInfo->commission,
                    ]);
                }

                $bookingController->sendMail($bookingInfo);

                // Nettoyage de la session
                Session::forget([
                    'event_id',
                    'selTickets',
                    'arrData',
                    'payment_id',
                    'discount',
                    'total_early_bird_dicount',
                    'grand_total',
                    'quantity',
                    'tax'
                ]);

                return view('frontend.customer.dashboard', $bookingInfo);
            } else {
                Log::warning('Paiement échoué', ['transaction_id' => $bookingId]);
                return response()->json(['message' => 'Paiement échoué.']);
            }
        } catch (Exception $e) {
            dd($e);
            Log::error('Erreur dans la notification CinetPay', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur.'], 500);
        }
    }


    private function checkPaymentStatus($transactionId)
    {
        try {
            // Configuration CinetPay
            $cinetpay = OnlineGateway::where('keyword', 'cinetpay')->firstOrFail();
            $info = json_decode($cinetpay->information, true);

            $cinetpayUrl = 'https://api-checkout.cinetpay.com/v2/payment/check';

            $client = new \GuzzleHttp\Client();

            $response = $client->post($cinetpayUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'apikey' => $info['api_key'], // ← Ici la correction
                    'site_id' => $info['site_id'],
                    'transaction_id' => $transactionId,
                ],
                'timeout' => 30,
            ]);



            $body = json_decode((string) $response->getBody(), true);

            if (isset($body['code']) && $body['code'] == '00') {
                return [
                    'status' => true,
                    'code' => $body['code'],
                    'message' => $body['message'] ?? 'Paiement confirmé',
                ];
            } else {
                return [
                    'status' => false,
                    'code' => $body['code'] ?? 'unknown',
                    'message' => $body['message'] ?? 'Échec de la transaction',
                ];
            }
        } catch (Exception $e) {
            dd($e);
            Log::error('Erreur lors de la vérification du paiement CinetPay', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'code' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }




    public function return()
    {
        return redirect()->route('success.page')->with('success', 'Votre paiement a été traité avec succès.');
    }

    public function callback()
    {
        return response()->json(['message' => 'Callback reçu.']);
    }

    public function cancel()
    {
        return redirect()->route('check-out')->with('error', 'Paiement annulé.');
    }
}
