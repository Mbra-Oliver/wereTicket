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
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'address' => 'required|string|max:500',
                'gateway' => 'required|string',
                'state' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'zip_code' => 'nullable|string|max:20',
            ], [
                'fname.required' => __('The first name field is required.'),
                'lname.required' => __('The last name field is required.'),
                'email.required' => __('The email field is required.'),
                'email.email' => __('The email must be a valid email address.'),
                'phone.required' => __('The phone field is required.'),
                'country.required' => __('The country field is required.'),
                'address.required' => __('The address field is required.'),
                'city.required' => __('The city field is required.'),
                'gateway.required' => __('Please select a payment method.'),
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

                'state' => $request->state ?? null,
                'city' => $request->city ?? null,
                'zip_code' => $request->zip_code ?? null,
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

            // Convertir le pays en code ISO-2 en majuscules
            $countryCode = self::getCountryCodeISO2($request->country ?? 'CI');

            $dataToSend = [
                'transaction_id' => $bookingInfo->booking_id, // Utiliser -> car bookingInfo est un objet
                'amount' => $payable_amount, // Corrigé pour utiliser la vraie variable
                'currency' => 'XOF',
                'customer_name' => $request->fname,
                'customer_surname' => $request->lname,
                'customer_email' => $request->email,
                'customer_phone_number' => $request->phone,
                'customer_address' => $request->address,
                'customer_city' => $request->city ?? $request->address,
                'customer_state' => $request->state ?? '',
                'customer_country' => $countryCode,
                'invoice_data' => [
                    'id' => $event_id,
                    'name' => $product->event_type ?? 'Event',
                    'price' => $payable_amount,
                ],
                'description' => 'Achat de ticket pour l\'événement : ' . ($product->name ?? 'Event'),
                'notify_url' => route('event_booking.cinetpay.notify', $bookingInfo->booking_id),
                'return_url' => route('event_booking.cinetpay.return', ['eventId' => $bookingInfo->booking_id]),
                'callback_url' => route('event_booking.cinetpay.notify', $bookingInfo->booking_id),
                'channels' => 'ALL',
                'metadata' => json_encode([
                    'event_id' => $event_id,
                ]),
                'customer_zip_code' => $request->zip_code ?? '00225',
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
            Log::error('Erreur dans makePayment', ['message' => $e->getMessage()]);
            return redirect()->route('check-out')->with('error',  $e->getMessage() ?? 'Erreur lors de l\'initialisation du paiement.');
        }
    }
    
    
    // Cette méthode est spécifiquement pour le webhook de notification
  // Cette méthode est spécifiquement pour le webhook de notification
    public function notify(Request $request, $bookingId)
    {
        // Lognez toutes les données reçues pour le débogage
        Log::info('CinetPay Notify - Données reçues', [
            'method' => $request->method(),
            'all' => $request->all(),
            'bookingId' => $bookingId,
            'headers' => $request->headers->all()
        ]);

        try {
            if (!$bookingId) {
                Log::error('CinetPay Notify - ID de transaction manquant');
                return response()->json(['status' => 'error', 'message' => 'Transaction ID manquant'], 400);
            }

            // Recherchez la réservation directement par son ID
            $bookingInfo = Booking::where('booking_id', $bookingId)->first();
            
            if (!$bookingInfo) {
                Log::error('CinetPay Notify - Réservation non trouvée', ['booking_id' => $bookingId]);
                return response()->json(['status' => 'error', 'message' => 'Réservation introuvable'], 404);
            }

            // Vérifier le statut du paiement
            $checkResult = $this->checkPaymentStatus($bookingId);
            Log::info('CinetPay Notify - Résultat de vérification', $checkResult);

            if ($checkResult['code'] === '00') {
                // Si le paiement n'est pas déjà marqué comme réussi
                if ($bookingInfo->paymentStatus != 1) {
                    DB::beginTransaction();
                    try {
                        // Récupérer les données nécessaires
                        $event_id = $bookingInfo->event_id;
                        
                        // Générer la facture
                        $bookingController = new BookingController();
                        $invoice = $bookingController->generateInvoice($bookingInfo, $event_id);
                        
                        // Mettre à jour la réservation
                        $bookingInfo->update([
                            'invoice' => $invoice,
                            'paymentStatus' => 1
                        ]);
                        
                        // Mettre à jour les revenus
                        $earning = Earning::first();
                        $earning->total_revenue += $bookingInfo->price + $bookingInfo->tax;
                        
                        if ($bookingInfo->organizer_id) {
                            $earning->total_earning += ($bookingInfo->tax + $bookingInfo->commission);
                        } else {
                            $earning->total_earning += $bookingInfo->price + $bookingInfo->tax;
                        }
                        $earning->save();
                        
                        // Stocker la transaction
                        $bookingInfo['paymentStatus'] = 1;
                        $bookingInfo['transaction_type'] = 1;
                        storeTranscation($bookingInfo);
                        
                        // Gérer l'organisateur si nécessaire
                        if ($bookingInfo->organizer_id) {
                            storeOrganizer([
                                'organizer_id' => $bookingInfo->organizer_id,
                                'price' => $bookingInfo->price,
                                'tax' => $bookingInfo->tax,
                                'commission' => $bookingInfo->commission,
                            ]);
                        }
                        
                        // Envoyer l'email de confirmation
                        $bookingController->sendMail($bookingInfo);
                        
                        DB::commit();
                        Log::info('CinetPay Notify - Paiement traité avec succès', ['booking_id' => $bookingId]);
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('CinetPay Notify - Erreur lors du traitement du paiement', [
                            'booking_id' => $bookingId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                    }
                } else {
                    Log::info('CinetPay Notify - Paiement déjà traité', ['booking_id' => $bookingId]);
                }
                
                // Retourner une réponse de succès simple pour le webhook
                return response()->json(['status' => 'success', 'message' => 'Paiement traité avec succès']);
            } else {
                Log::warning('CinetPay Notify - Paiement échoué', [
                    'booking_id' => $bookingId,
                    'status' => $checkResult
                ]);
                return response()->json(['status' => 'error', 'message' => 'Paiement échoué'], 400);
            }
        } catch (Exception $e) {
            Log::error('CinetPay Notify - Exception', [
                'booking_id' => $bookingId ?? 'non fourni',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage() ?? 'Erreur serveur'], 500);
        }
    }


public function returnFromPayment($bookingId)
    {
        Log::info('Return from CinetPay payment', ['booking_id' => $bookingId]);
        
        try {
            if (!$bookingId) {
                return redirect()->route('check-out')->with('error', 'ID de réservation manquant.');
            }

            // Récupérer les informations de réservation directement depuis la base de données
            $bookingInfo = Booking::where('booking_id', $bookingId)->first();
            
            if (!$bookingInfo) {
                Log::error('Réservation non trouvée', ['booking_id' => $bookingId]);
                return redirect()->route('check-out')->with('error', 'Réservation introuvable.');
            }
            
            // Récupérer l'événement associé à la réservation
            $event = Event::find($bookingInfo->event_id);
            
            if (!$event) {
                Log::error('Événement non trouvé', ['event_id' => $bookingInfo->event_id]);
                return redirect()->route('check-out')->with('error', 'Événement introuvable.');
            }

            // Vérifier le statut du paiement
            $checkResult = $this->checkPaymentStatus($bookingId);
            Log::info('Résultat de vérification du paiement', $checkResult);

            if ($checkResult['code'] === '00') {
                // Procéder seulement si le paiement n'est pas déjà marqué comme réussi
                if ($bookingInfo->paymentStatus != 1) {
                    DB::beginTransaction();
                    try {
                        $bookingController = new BookingController();
                        
                        // Générer la facture
                        $invoice = $bookingController->generateInvoice($bookingInfo, $bookingInfo->event_id);
                        
                        // Mettre à jour la réservation
                        $bookingInfo->update([
                            'invoice' => $invoice,
                            'paymentStatus' => 1
                        ]);
                        
                        // Mettre à jour les revenus
                        $earning = Earning::first();
                        $earning->total_revenue += $bookingInfo->price + $bookingInfo->tax;
                        
                        if ($bookingInfo->organizer_id) {
                            $earning->total_earning += ($bookingInfo->tax + $bookingInfo->commission);
                        } else {
                            $earning->total_earning += $bookingInfo->price + $bookingInfo->tax;
                        }
                        $earning->save();
                        
                        // Stocker la transaction
                        $bookingInfo['paymentStatus'] = 1;
                        $bookingInfo['transaction_type'] = 1;
                        storeTranscation($bookingInfo);
                        
                        // Gérer l'organisateur si nécessaire
                        if ($bookingInfo->organizer_id) {
                            storeOrganizer([
                                'organizer_id' => $bookingInfo->organizer_id,
                                'price' => $bookingInfo->price,
                                'tax' => $bookingInfo->tax,
                                'commission' => $bookingInfo->commission,
                            ]);
                        }
                        
                        // Envoyer l'email de confirmation
                        $bookingController->sendMail($bookingInfo);
                        
                        DB::commit();
                        
                        // Nettoyage de la session après traitement réussi
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
                        
                        Log::info('Paiement traité avec succès', ['booking_id' => $bookingId]);
                        
                        // Rediriger vers le tableau de bord utilisateur ou une page personnalisée de succès
                        return redirect()->route('customer.dashboard');
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Erreur lors du traitement du paiement', [
                            'booking_id' => $bookingId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        return redirect()->route('check-out')->with('error', 'Une erreur est survenue lors du traitement de votre paiement: ' . $e->getMessage());
                    }
                } else {
                    // Paiement déjà traité
                    Log::info('Paiement déjà traité', ['booking_id' => $bookingId]);
                    return redirect()->route('customer.dashboard')->with('success', 'Votre paiement a été traité avec succès!');
                }
            } else {
                // Paiement échoué
                Log::warning('Paiement échoué', ['booking_id' => $bookingId, 'status' => $checkResult]);
                return redirect()->route('check-out')->with('error', 'Votre paiement a échoué. Veuillez réessayer.');
            }
        } catch (Exception $e) {
            Log::error('Erreur dans returnFromPayment', [
                'booking_id' => $bookingId ?? 'non fourni',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('check-out')->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }


    public function checkPaymentStatus($transactionId)
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

    /**
     * Convertit un nom de pays ou code pays en code ISO-2 en majuscules
     * 
     * @param string $country Le nom du pays ou le code pays
     * @return string Code ISO-2 en majuscules (ex: "CI", "FR", "US")
     */
    private static function getCountryCodeISO2($country)
    {
        // Si c'est déjà un code ISO-2 (2 caractères), le convertir en majuscules
        if (strlen(trim($country)) == 2) {
            return strtoupper(trim($country));
        }

        // Mapping des noms de pays vers codes ISO-2
        $countryMapping = [
            // Afrique de l'Ouest
            'côte d\'ivoire' => 'CI',
            'cote d\'ivoire' => 'CI',
            'ivory coast' => 'CI',
            'senegal' => 'SN',
            'sénégal' => 'SN',
            'mali' => 'ML',
            'burkina faso' => 'BF',
            'niger' => 'NE',
            'guinea' => 'GN',
            'guinée' => 'GN',
            'benin' => 'BJ',
            'bénin' => 'BJ',
            'togo' => 'TG',
            'ghana' => 'GH',
            'nigeria' => 'NG',
            'cameroon' => 'CM',
            'cameroun' => 'CM',
            
            // Europe
            'france' => 'FR',
            'spain' => 'ES',
            'espagne' => 'ES',
            'italy' => 'IT',
            'italie' => 'IT',
            'germany' => 'DE',
            'allemagne' => 'DE',
            'united kingdom' => 'GB',
            'royaume-uni' => 'GB',
            'belgium' => 'BE',
            'belgique' => 'BE',
            'switzerland' => 'CH',
            'suisse' => 'CH',
            
            // Amériques
            'united states' => 'US',
            'états-unis' => 'US',
            'canada' => 'CA',
            'mexico' => 'MX',
            'mexique' => 'MX',
            'brazil' => 'BR',
            'brésil' => 'BR',
            
            // Asie
            'china' => 'CN',
            'chine' => 'CN',
            'japan' => 'JP',
            'japon' => 'JP',
            'india' => 'IN',
            'inde' => 'IN',
            'south korea' => 'KR',
            'corée du sud' => 'KR',
            
            // Autres
            'australia' => 'AU',
            'australie' => 'AU',
            'south africa' => 'ZA',
            'afrique du sud' => 'ZA',
            'egypt' => 'EG',
            'egypte' => 'EG',
            'morocco' => 'MA',
            'maroc' => 'MA',
            'algeria' => 'DZ',
            'algérie' => 'DZ',
            'tunisia' => 'TN',
            'tunisie' => 'TN',
        ];

        // Normaliser l'entrée : minuscules et suppression des accents
        $normalizedCountry = mb_strtolower(trim($country));
        $normalizedCountry = self::removeAccents($normalizedCountry);

        // Chercher dans le mapping
        if (isset($countryMapping[$normalizedCountry])) {
            return $countryMapping[$normalizedCountry];
        }

        // Si non trouvé, essayer de chercher par correspondance partielle
        foreach ($countryMapping as $name => $code) {
            if (strpos($normalizedCountry, $name) !== false || strpos($name, $normalizedCountry) !== false) {
                return $code;
            }
        }

        // Par défaut, retourner CI (Côte d'Ivoire) si non trouvé
        Log::warning('Code pays ISO-2 non trouvé', ['country' => $country]);
        return 'CI';
    }

    /**
     * Supprime les accents d'une chaîne
     * 
     * @param string $string
     * @return string
     */
    private static function removeAccents($string)
    {
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ç' => 'c', 'ñ' => 'n',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ý' => 'Y',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        return strtr($string, $accents);
    }
}
