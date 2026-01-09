<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\IyzicoEventPendingPayment;
use App\Jobs\IyzicoProductOrderPendingPayment;
use App\Models\Event\Booking;
use App\Models\ShopManagement\ProductOrder;
use Illuminate\Support\Facades\Artisan;

class CronJobController extends Controller
{
    public function checkIyzicoPendingPayment()
    {
        try {
            /*```````````````````````````````````````````````````````
            ```````````Check Iyzico event pending bookings``````````
            -------------------------------------------------------*/
            $event_bookings = Booking::where([['paymentStatus', 'pending'], ['paymentMethod', 'Iyzico']])->get();
            if (count($event_bookings) > 0) {
                foreach ($event_bookings as $key => $event_booking) {
                    if (!is_null($event_booking->conversation_id)) {
                        IyzicoEventPendingPayment::dispatch($event_booking->id);
                    }
                }
            }
            /*```````````````````````````````````````````````````````
            ```````````Check Iyzico product purchase pending bookings``````````
            -------------------------------------------------------*/
            $productOrders = ProductOrder::where([['payment_status', 'pending'], ['method', 'Iyzico']])->get();
            if (count($productOrders) > 0) {
                foreach ($productOrders as $key => $productOrder) {
                    if (!is_null($productOrder->conversation_id)) {
                        IyzicoProductOrderPendingPayment::dispatch($productOrder->id);
                    }
                }
            }
        } catch (\Throwable $th) {
        }
    }
    public function sendTicket()
    {
        try {
            Artisan::call('queue:work', [
                '--stop-when-empty' => true, // To avoid infinite running in case
            ]);
        } catch (\Throwable $th) {
          
        }
    }

    public function sendPushNotificationPhone()
    {
      try {

        Artisan::call('queue:work', [
          '--stop-when-empty' => true, // To avoid infinite running in case
        ]);
      } catch (\Throwable $th) {
      }
    }

}
