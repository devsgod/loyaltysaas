<?php

namespace Platform\Controllers\App;

use App\User;
use App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TwoCheckoutController extends \App\Http\Controllers\Controller
{
    /*
     |--------------------------------------------------------------------------
     | 2Checkout Controller
     |--------------------------------------------------------------------------
     */
  
    /**
     * IPN call /api/webhooks/2checkout/ipn
     *
     * @return null
     */
    public static function postIpn(Request $request) {
        $test_order = ($request->TEST_ORDER == 1) ? true : false;
        $remote_user_id = $request->AVANGATE_CUSTOMER_REFERENCE;
        $user_uuid = $request->EXTERNAL_CUSTOMER_REFERENCE;
        $product_id_2checkout = $request->IPN_PID[0];
        $expires = $request->IPN_LICENSE_EXP[0];
        $sendMail = true;
        $sendUserMail = false;

        if ($user_uuid === null || $user_uuid == '') {
          return;
        }
      
        $user = User::whereUuid($user_uuid)->first();

        if ($user === null) {
          return;
        }

        $all_input = '';
        foreach ($request->all() as $k => $v) {
          if (is_array($k)) {
            foreach ($k as $v_k => $v_v) {
              $all_input .= "\t" . $v_k . " = " . $v_v . PHP_EOL . PHP_EOL;
            }
          } else {
            if (is_array($v)) {
              foreach ($v as $v_k => $v_v) {
                $all_input .= "\t" . $k . "[" . $v_k . "] = " . $v_v . PHP_EOL . PHP_EOL;
              }
            } else {
              $all_input .= $k . " = " . $v . PHP_EOL . PHP_EOL;
            }
          }
        }
      
        // $alert_name == 'subscription_payment_succeeded' || 
        $alert_name = $request->ORDERSTATUS;

        if ($alert_name == 'COMPLETE') {
          $plan = \Platform\Models\Plan::where('product_id_2checkout', $product_id_2checkout)->first();

          if ($plan !== null) {
            //if (! $test_order) {
              $user->plan_id = $plan->id;
              $user->expires = Carbon($expires);
              $user->save();
            //}

            $subject = "Payment complete";
            $body_top = "Customer: " . $request->FIRSTNAME . " " . $request->LASTNAME . " (" . $user->name . ", " . $user->email . ")" . PHP_EOL . PHP_EOL;
            $body_top .= "Plan: " . $plan->name . PHP_EOL . PHP_EOL;
            $body_top .= "2Checkout Customer ID: " . $request->AVANGATE_CUSTOMER_REFERENCE . PHP_EOL . PHP_EOL;
            $body_bottom = "";
            
            $sendUserMail = true;
            $user_subject = "Payment complete";
            $user_body_top = "Thank you for your purchase! Your subscription has been updated." . PHP_EOL . PHP_EOL;
            $user_body_top .= "You will receive an e-mail from 2Checkout Support.";
            $user_body_bottom = "";
          }
        } else {
            $sendMail = false;
            $subject = "Payment Event - " . $alert_name;
            $body_top = "See output below.";
            $body_bottom = $all_input;
        }

        if ($sendMail) {
          $email = new \stdClass;
          $email->app_name = $user->account->app_name;
          $email->app_url = '//' . $user->account->app_host;
          $email->from_name = $user->account->app_mail_name_from;
          $email->from_email = $user->account->app_mail_address_from;
          $email->to_name = $user->account->name;
          $email->to_email = $user->account->email;
          $email->subject = $subject;
          $email->body_top = $body_top;
          $email->cta_label = "Go to dashboard";
          $email->cta_url = '//' . $user->account->app_host . '/go#/login';
          $email->body_bottom = $body_bottom;

          Mail::send(new \App\Mail\SendMail($email));
        }

        if ($sendUserMail) {
          $email = new \stdClass;
          $email->app_name = $user->account->app_name;
          $email->app_url = '//' . $user->account->app_host;
          $email->from_name = $user->account->app_mail_name_from;
          $email->from_email = $user->account->app_mail_address_from;
          $email->to_name = $user->name;
          $email->to_email = $user->email;
          $email->subject = $user_subject;
          $email->body_top = $user_body_top;
          $email->cta_label = "Go to dashboard";
          $email->cta_url = '//' . $user->account->app_host . '/go#/login';
          $email->body_bottom = $user_body_bottom;

          Mail::send(new \App\Mail\SendMail($email));
        }

        // 2Checkout hash validation, get all POST parameters except HASH
        $request = request()->except('HASH');
        $remote_hash = request()->input('HASH', '');

        $date = date('YmdGis');
        $secret_key = config('general.2checkout_key');

        $hmac_string = TwoCheckoutController::array_to_string($request);
        $hash = TwoCheckoutController::hmac($secret_key, $hmac_string);

        if ($remote_hash == $hash) {
          // It's a valid request
          $IPN_PID = request()->input('IPN_PID', '');
          $IPN_PNAME = request()->input('IPN_PNAME', '');
          $IPN_DATE = request()->input('IPN_DATE', '');
          $LICENSE_PRODUCT = request()->input('IPN_PID', '');

          $your_signature = TwoCheckoutController::hmac($secret_key, TwoCheckoutController::array_to_string([$IPN_PID, $IPN_PNAME, $IPN_DATE, $date]));

          echo '<EPAYMENT>' . $date . '|' . $your_signature . '</EPAYMENT>';
        }
    }

    public static function hmac($key, $data){
      $b = 64; // byte length for md5
      if (strlen($key) > $b) {
       $key = pack('H*',md5($key));
      }
      $key  = str_pad($key, $b, chr(0x00));
      $ipad = str_pad('', $b, chr(0x36));
      $opad = str_pad('', $b, chr(0x5c));
      $k_ipad = $key ^ $ipad ;
      $k_opad = $key ^ $opad;
      return md5($k_opad  . pack('H*',md5($k_ipad . $data)));
    }

    public static function array_to_string($data){
      $return = '';

      if(!is_array($data)){
        $return	.= strlen($data).$data;
      }
      else{
        foreach($data as $val){
          if(!is_array($val)){
            $return	.= strlen($val).$val;
          } else {
            foreach($val as $val2){
              $return	.= strlen($val2).$val2;
            }
          }
        }		
      }
      return $return;
    }
}