<?php

namespace App\Jobs;

use App\Http\Controllers\BackEnd\Event\EventBookingController;
use App\Models\Event\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BookingBackendInvoiceJob implements ShouldQueue
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
        $booking = Booking::where('id', $this->booking_id)->first();

        $enrol = new EventBookingController();
        try {

            $invoice = $enrol->generateInvoice($booking);

            $booking->update([
                'invoice' => $invoice
            ]);

            $bookingInfo = $booking;

            //storeTransaction
            $bookingInfo['paymentStatus'] = 1;
            $bookingInfo['transcation_type'] = 1;

            storeTranscation($bookingInfo);

            //store amount to organizer
            $organizerData['organizer_id'] = $booking->organizer_id;
            $organizerData['price'] = $booking->price;
            $organizerData['commission'] = $booking->commission;
            $organizerData['organizer_id'] = $booking->organizer_id;
            storeOrganizer($organizerData);

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
            //end unlink qr code

            $enrol->sendMail($booking, $booking, 'Booking approved');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
