<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public  function core()
    {
        $sets = DB::table('settings');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
    }

    public  function  adex_key()
    {
        $sets = DB::table('adex_key');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
    }

    public  function  general()
    {
        $sets = DB::table('general');
        if ($sets->count() == 1) {
            return $sets->first();
        } else {
            return null;
        }
    }

    public function feature()
    {
        return  DB::table('feature')->get();
    }


    public  function updateData($data, $tablename, $tableid)
    {
        return  DB::table($tablename)
            ->where($tableid)
            ->update($data);
    }


    public  function generatetoken($req)
    {
        if (DB::table('user')->where('id', $req)->count() == 1) {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $pin =  mt_rand(1000000, 9999999)
                . mt_rand(1000000, 9999999)
                . $characters[rand(0, strlen($characters) - 1)];
            $secure_key = str_shuffle($pin);
            DB::table('user')->where('id', $req)->update(['adex_key' => $secure_key]);
            return $secure_key;
        } else {
            return null;
        }
    }

    public function generateapptoken($key)
    {
        if (DB::table('user')->where('id', $key)->count() == 1) {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $pin =  mt_rand(1000000, 9999999)
                . mt_rand(1000000, 9999999)
                . $characters[rand(0, strlen($characters) - 1)];
            $secure_key = str_shuffle($pin);
            DB::table('user')->where('id', $key)->update(['app_key' => $secure_key]);
            return $secure_key;
        } else {
            return null;
        }
    }
    public function verifyapptoken($key)
    {
        if (DB::table('user')->where('app_key', $key)->count() == 1) {
            $user = DB::table('user')->where('app_key', $key)->first();
            return $user->id;
        } else {
            return null;
        }
    }

    public  function verifytoken($request)
    {
        if (DB::table('user')->where('adex_key', $request)->count() == 1) {
            $user = DB::table('user')->where('adex_key', $request)->first();
            return $user->id;
        } else {
            return null;
        }
    }


    public  function generate_ref($title)
    {
        $code = random_int(100000, 999999);
        $me = random_int(1000, 9999);
        $app_name = env('APP_NAME');
        $ref = "|$app_name|$title|$code|adex-dev-$me";
        return $ref;
    }
    public function purchase_ref($d)
    {
        return uniqid($d);
    }
    public  function insert_stock($username)
    {
        $check_first = DB::table('wallet_funding')->where('username', $username);
        if ($check_first->count() == 0) {
            $values = array('username' => $username);
            DB::table('wallet_funding')->insert($values);
        }
    }
    public function inserting_data($table, $data)
    {
        return DB::table($table)->insert($data);
    }
    public  function monnify_account($username)
    {
        $this_Controller =   new Controller;
        $check_first = DB::table('user')->where('username', $username);
        if ($check_first->count() == 1) {
            $get_user = $check_first->get()[0];
            $setting =  $this_Controller->core();
            $adex_key = $this_Controller->adex_key();
            
            if(is_null($get_user->paypalmpay)){
      $response = Http::withHeaders([
          'Authorization' => 'Bearer dd31b61b02d0b91bf84dfdaae22f2fa3f2b6d57b83f4ea737a0e7f1cf81cb2ad3d15a4919ac7b38f25d117d04226acf7b15982fa8586348586a84db2',
          'api-key' => '409115bb96fa828d422b0e3017eee5bb6b50cfb3'
          ])->post('https://api.paymentpoint.co/api/v1/createVirtualAccount', [
              'email' => $get_user->email,
              'name' => $get_user->username,
              'phoneNumber' => $get_user->phone,
              'bankCode' => ['20946'],
              'businessId' => '8dcb31d6257646ce88432d15f127997b4d31011a'
              ]);
              file_put_contents('response_h.json', json_encode($response->json()));
      if($response->successful()){
         $data = $response->json();
         if(isset($data['bankAccounts'])){
             foreach($data['bankAccounts'] as $bank){
                 if($bank['bankCode'] == '20946'){
                     DB::table('user')->where('id', $get_user->id)->update(['paypalmpay' => $bank['accountNumber']]);
                 }
             }
         }
      }
   } 
            if(is_null($get_user->palmpay)){
      $response = Http::withHeaders([
          'Authorization' => 'Bearer 038c598d03bacfcc134ab28a2fb99dcfe1cc9bc85f16bd51cdf1c644ac6f42351bdf77f8ea38b4f7e2d8ca3570864ec6352a84efd3942ee75e78b226',
          'api-key' => '592645ebc7e4e67420b2da7895f2b476c459e873'
          ])->post('https://api.xixapay.com/api/v1/createVirtualAccount', [
              'email' => $get_user->email,
              'name' => $get_user->username,
              'phoneNumber' => $get_user->phone,
              'bankCode' => ['20867'],
              'businessId' => '4c80b9738aa647fdfa19042f33aa1d297a70203e'
              ]);
              file_put_contents('response_h.json', json_encode($response->json()));
      if($response->successful()){
         $data = $response->json();
         if(isset($data['bankAccounts'])){
             foreach($data['bankAccounts'] as $bank){
                 if($bank['bankCode'] == '20867'){
                     DB::table('user')->where('id', $get_user->id)->update(['palmpay' => $bank['accountNumber']]);
                 }
             }
         }
      }
   }

      if ($setting->monnify_atm == 1) {
    $base_monnify = base64_encode("{$adex_key->mon_app_key}:{$adex_key->mon_sk_key}");
    if (empty($get_user->wema) || empty($get_user->rolex) || empty($get_user->autofund) || $get_user->autofund != 'ACTIVE') {
        $accessToken = null;
        $response = Http::withHeaders(["Authorization" => "Basic $base_monnify"])->post('https://api.monnify.com/api/v1/auth/login');
        $result = $response->json();
        file_put_contents('monnify_rese.json', json_encode($result));
        $accessToken = $result['responseBody']['accessToken'] ?? null;
        if ($accessToken) {
            $ref = $this_Controller->generate_ref('MONNIFY');
            $url = 'https://api.monnify.com/api/v2/bank-transfer/reserved-accounts';

            $data = [
                'accountReference' => $ref,
                'accountName' => $get_user->username,
                'currencyCode' => 'NGN',
                'contractCode' => $adex_key->mon_con_num,
                'customerEmail' => $get_user->email,
                'bvn' =>  $adex_key->mon_bvn,
                'customerName' => $get_user->username,
                'getAllAvailableBanks' => false,
                'preferredBanks' => ["50515", "035", "232"],
            ];

            $headers = [
                "Authorization" => "Bearer $accessToken",
                "Content-Type" => "application/json",
            ];

            $response = Http::withHeaders($headers)->post($url, $data);
            $value = $response->json();
            
            file_put_contents('monnify_response.json', json_encode($value));

            if ($value["requestSuccessful"] ?? false) {
                $accounts = $value["responseBody"]["accounts"] ?? [];

                foreach ($accounts as $account) {
                    switch ($account["bankCode"]) {
                        case "035":
                            $wema = $account["accountNumber"];
                            break;
                        case "50515":
                            $rolex = $account["accountNumber"];
                            break;
                        case "232":
                            $str = $account["accountNumber"];
                            break;
                    }
                }

                $data_update = [
                    'wema' => $wema ?? null,
                    'rolex' => $rolex ?? null,
                    'sterlen' => $str ?? null,
                    'autofund' => 'ACTIVE',
                    'monify_ref' => $ref
                ];

                $tableid = ['id' => $get_user->id];
                $this_Controller->updateData($data_update, 'user', $tableid);
            }
        }
    }
}
          
        }
    }
    public function system_date()
    {
        return  Carbon::now("Africa/Lagos")->toDateTimeString();
    }
}
