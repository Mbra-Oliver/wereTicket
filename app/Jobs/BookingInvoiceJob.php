<?php

namespace App\Jobs;

use App\Http\Controllers\FrontEnd\Event\BookingController;
use App\Models\Event\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BookingInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; 
    public $tries = 3;   

    public $booking_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($booking_id)
    {
        $this->booking_id = $booking_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bookingInfo = Booking::where('id', $this->booking_id)->first();

        $enrol = new BookingController();
        try {

            // generate an invoice in pdf format
            $invoice = $enrol->generateInvoice($bookingInfo, $bookingInfo->event_id);

            //unlink qr code 
            if (
                $bookingInfo->variation != null
            ) {
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
        } catch (\Exception $e) {
            throw $e;  
        }
    }
}
