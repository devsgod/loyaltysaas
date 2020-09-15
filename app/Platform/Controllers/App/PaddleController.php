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

class PaddleController extends \App\Http\Controllers\Controller
{
    /*
     |--------------------------------------------------------------------------
     | Paddle Controller
     |--------------------------------------------------------------------------
     */
  
    /**
     * Webhook call /api/webhooks/paddle
     *
     * @return null
     */
    public static function postWebhook(Request $request) {

        $passthrough = json_decode($request->passthrough);
        $user_uuid = $passthrough->uuid;
        $sendMail = true;
        $sendUserMail = false;

        $user = User::whereUuid($user_uuid)->first();

        if ($user === null) {
          return;
        }

        $all_input = '';
        foreach ($request->all() as $k => $v) {
          $all_input .= $k . " = " . $v . "<br>";
        }
      
        // $alert_name == 'subscription_payment_succeeded' || 
        $alert_name = $request->alert_name;

        if ($alert_name == 'subscription_created' || $alert_name == 'subscription_payment_succeeded') { // First "subscription_created" then "subscription_payment_succeeded"
          $product_id_paddle = $request->subscription_plan_id;
          $plan = \Platform\Models\Plan::where('product_id_paddle', $product_id_paddle)->first();

          if ($plan !== null) {

            $subject = "Subscription payment success";

            if ($alert_name == 'subscription_payment_succeeded') { 
              $user->plan_id = $plan->id;
              $user['meta->subscription_receipt_url'] = $request->receipt_url;
              $body_bottom = "Receipt: " . $request->receipt_url . PHP_EOL;

              $sendUserMail = true;
              $user_subject = "Payment received";
              $user_body_top = "Thank you for your purchase! Your subscription has been updated." . PHP_EOL . PHP_EOL;
              $user_body_top .= "You will receive an e-mail from Paddle.com.";
              $user_body_bottom = "Your receipt: " . $request->receipt_url;
            }

            if ($alert_name == 'subscription_created') { 
              $subject = "Subscription created";
              $user->expires = Carbon($request->next_bill_date . ' ' . Carbon::now()->format('H:i:s'))->addDays(config('system.trial_days'));
              $user['meta->subscription_cancel_url'] = $request->cancel_url;
              $user['meta->subscription_update_url'] = $request->update_url;
              $body_bottom = "Cancel subscription: " . $request->cancel_url . PHP_EOL;
            }

            $user->save();

            $body_top = "Customer: " . $request->email . " (" . $user->name . ", " . $user->email . ")" . PHP_EOL . PHP_EOL;
            $body_top .= "Plan: " . $plan->name . PHP_EOL;
          }
        } elseif ($alert_name == 'subscription_cancelled') {
            $plan = \Platform\Models\Plan::where('id', $user->plan_id)->first();

            //$user->plan_id = null;
            $user->expires = Carbon($request->cancellation_effective_date . ' ' . Carbon::now()->format('H:i:s'))->addDays(config('system.trial_days'));
            $user['meta->subscription_receipt_url'] = null;
            $user['meta->subscription_cancel_url'] = null;
            $user['meta->subscription_update_url'] = null;
            $user->save();

            $subject = "Subscription cancelled";
            $body_top = "Customer: " . $request->email . " (" . $user->name . ", " . $user->email . ")" . PHP_EOL . PHP_EOL;
            $body_top .= "Cancelled plan: " . $plan->name . PHP_EOL;
            $body_bottom = "";
          
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
    }
}