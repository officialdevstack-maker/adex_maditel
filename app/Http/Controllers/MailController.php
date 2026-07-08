<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    //adex developer
    public static function send_mail($user_data, $template) {
        try{
       Mail::send($template,$user_data, function($message) use ($user_data) {
            $message->to($user_data['email'], $user_data['username'])->subject
               ($user_data['title']);
            $message->from(env('MAIL_FROM_ADDRESS'), $user_data['app_name']);
         });
     if(Mail::failures()){
        return false;
     }else{
        return true;
     }
        }catch(\Exception $e){
            
        }
     }

}
