<?php

namespace App\Jobs;

use App\Models\FcmToken;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $title;
    public $message;
    public $buttonName;
    public $buttonURL;

    public function __construct($title, $message, $buttonName, $buttonURL)
    {
      $this->title = $title;
      $this->message = $message;
      $this->buttonName = $buttonName;
      $this->buttonURL = $buttonURL;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $title = $this->title;
      $message = $this->message;
      $buttonName = $this->buttonName;
      $buttonURL = $this->buttonURL;

     FcmToken::query()
      ->whereNotNull('token')
      ->select('token')
      ->groupBy('token')
      ->orderByRaw('NULL')
      ->chunk(20, function ($tokens) use ($title, $message, $buttonName, $buttonURL) {
        foreach ($tokens as $token) {
          FirebaseService::pushNotification($title, $message, $buttonName, $buttonURL, $token->token);
        }
      });
    }
}
