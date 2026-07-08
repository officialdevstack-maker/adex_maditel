<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class  Banks extends Controller
{
 public function GetBanksArray(Request $request)
{
      $allowedOrigins = explode(',', env('ADEX_APP_KEY'));
$origin = $request->header('Origin');
$authorization = $request->header('Authorization');
if (in_array($origin, $allowedOrigins) || env('ADEX_DEVICE_KEY') === $authorization) {
        if (!empty($request->id)) {
           $auth_user = DB::table('user')->where('status', 1)->where(function($query) use ($request) {
           $query->orWhere('id', $this->verifytoken($request->id))
              ->orWhere('id', $this->verifyapptoken($request->id));
           })->first();

            $setting = DB::table('settings')->first();
            $banks_array = [];
            
             if (!is_null($auth_user->paypalmpay)) {
                $banks_array[] = [
                    "name" => "PALMPAY",
                    "account" => $auth_user->paypalmpay,
                    "accountType" => $auth_user->paypalmpay === null,
                    'charges' => '0.5 capped at #50',
                ];
            }
            // if (!is_null($auth_user->palmpay)) {
            //     $banks_array[] = [
            //         "name" => "PALMPAY",
            //         "account" => $auth_user->palmpay,
            //         "accountType" => $auth_user->palmpay === null,
            //         'charges' => '50 NAIRA',
            //     ];
            // }
    
            if (!is_null($auth_user->rolex)) {
                $banks_array[] = [
                    "name" => "MONIEPOINT",
                    "account" => $auth_user->rolex,
                    "accountType" => $auth_user->rolex === null,
                    'charges' => $setting->charges,
                ];
            }

            // if (!is_null($auth_user->wema)) {
            //     $banks_array[] = [
            //         "name" => "WEMA",
            //         "account" => $auth_user->wema,
            //         "accountType" => $auth_user->wema === null,
            //         'charges' => $setting->charges,
            //     ];
            // }

            // if (!is_null($auth_user->sterlen)) {
            //     $banks_array[] = [
            //         "name" => "STERLING",
            //         "account" => $auth_user->sterlen,
            //         "accountType" => $auth_user->sterlen === null,
            //         'charges' => $setting->charges,
            //     ];
            // }

            return response()->json(['status' => 'success', 'banks' => $banks_array]);
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Hey,Login To Continue'])->setStatusCode(403);
        }
    } else {
        return response()->json(['status' => 'fail', 'message' => 'Cannot Retrieve Banks'])->setStatusCode(403);
    }
}


}