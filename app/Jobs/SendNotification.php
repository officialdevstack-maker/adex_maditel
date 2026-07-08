<?php
// app/Jobs/SendNotification.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $token;
    protected $message;

    public function __construct($token, $message)
    {
     $this->token = $token; 
    $this->message = $message;
    }

    public function handle()
    {
        try{
         $firebase = (new Factory)
            ->withServiceAccount(__DIR__.'/../../config/firebase_credentials.json');
            
        // Compose the notification data
         $messaging = $firebase->createMessaging();
        $message = CloudMessage::fromArray([
            'notification' => [
                'title' => env('APP_NAME'),
                'body' => $this->message,
                 "sound" => "default",
                "badge" => "1",
            ],
            'topic' => 'all',
            // "token" => $this->token,
        ]);
       $messaging->send($message);
    }catch(\Exception $e){
        
    }
    }
}
