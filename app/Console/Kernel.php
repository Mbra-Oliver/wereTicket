<?php
namespace App\Console;

use App\Models\Booking;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\FrontEnd\PaymentGateway\CinetPayController;
use App\Http\Controllers\FrontEnd\Event\BookingController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Ajout de l'import manquant pour DB

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Toutes les 5 minutes
        $schedule->call(function () {
            
                    Log::info('🔔 Cron CinetPay check exécuté à '. now());
                    
            Booking::where('paymentStatus', 0)
                // optionnel : ne traiter que les enregistrements vieux de plus de 5 minutes
                ->where('created_at', '<', now()->subMinutes(5))
                ->chunkById(100, function ($bookings) {
                    $controller = app(CinetPayController::class);
                    $bookingController = app(BookingController::class); // Instanciation correcte du BookingController
                    
                    foreach ($bookings as $booking) {
                        try {
                            $result = $controller->checkPaymentStatus($booking->booking_id);
                            if (isset($result['code']) && $result['code'] === '00') {
                                // Mettre à jour la réservation comme si c'était fait dans le webhook
                                DB::transaction(function () use ($booking, $bookingController) {
                                    $invoice = $bookingController->generateInvoice($booking, $booking->event_id);
                                    $booking->update([
                                        'invoice'      => $invoice,
                                        'paymentStatus'=> 1,
                                    ]);
                                    // mettez à jour vos revenus, envoyez l'email, etc.
                                });
                                Log::info("Cron CinetPay : paiement confirmé", ['booking_id' => $booking->booking_id]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Cron CinetPay - Erreur vérification', [
                                'booking_id' => $booking->booking_id,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    }
                });
        })->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}