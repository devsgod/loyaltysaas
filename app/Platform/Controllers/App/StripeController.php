<?php namespace Platform\Controllers\App;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class StripeController extends \App\Http\Controllers\Controller {

  /*
   |--------------------------------------------------------------------------
   | Stripe Controller
   |--------------------------------------------------------------------------
   */

  /**
   * Create Stripe customer if necessary and subscribe to plan.
   */
  public function postToken(Request $request) {
    $plan_id = $request->plan_id;
    $stripe_plan_id = $request->stripe_plan_id;
    $token = $request->token;
    $email = $request->email;
    $type = $request->type;

    if ($token != null && auth()->check()) {
      $stripe_secret_key = config('general.stripe_secret_key');
      if ($stripe_secret_key === null) {
        abort(404);
      }

      // Set Stripe secret key
      \Stripe\Stripe::setApiKey($stripe_secret_key);

      $user = \App\User::where('id', auth()->user()->id)->first();
      $plan = \Platform\Models\Plan::where('id', $plan_id)->first();

      if (! empty($user) && ! empty($plan)) {
        // Customer exists?
        if ($user->remote_customer_id === null) {
          if ($user->previous_remote_customer_id !== null) {
            $stripe_customer_id = $user->previous_remote_customer_id;
            $user->remote_customer_id = $stripe_customer_id;
            $user->save();
          } else {
            // Create customer
            $account = app()->make('account');

            $customer = \Stripe\Customer::create(array(
              "description" => $account->app_name . ' - ' . $user->name . ' (' . $user->email . ') - ID: ' . $user->id,
              "source" => $token,
              "email" => $email
            ));

            $stripe_customer_id = $customer->id;

            $user->remote_customer_id = $stripe_customer_id;
            $user->save();
          }
        } else {
          $stripe_customer_id = $user->remote_customer_id;
        }

        // Charge or subscribe
        if ($stripe_plan_id === null) { // Lifetime
          $charge = \Stripe\Charge::create([
            'customer' => $stripe_customer_id,
            'amount'   => $plan->price,
            'currency' => $plan->currency_code,
          ]);

          $user->plan_id = $plan_id;
          $user->expires = null; // lifetime subscription never expires
          $user->save();

        } elseif (Str::startsWith($stripe_plan_id, 'plan_') || Str::startsWith($stripe_plan_id, 'price_')) { // Subscription
          $subscription = \Stripe\Subscription::create([
            'customer' => $stripe_customer_id, 
            'items' => [
              [
                'plan' => $stripe_plan_id
              ]
            ]
          ]);

          if (isset($subscription->plan)) {
            $expires = Carbon::now()->addMonths(1); // If an unknown billing interval is returned, add 1 month by default

            if (isset($subscription->plan->interval)) {
              $interval_count = (isset($subscription->plan->interval_count)) ? $subscription->plan->interval_count : 1;
              switch ($subscription->plan->interval) {
                case 'day': $expires = Carbon::now()->addDays($interval_count); break;
                case 'week': $expires = Carbon::now()->addWeeks($interval_count); break;
                case 'month': $expires = Carbon::now()->addMonths($interval_count); break;
                case 'year': $expires = Carbon::now()->addYears($interval_count); break;
              }
            }

            $user->plan_id = $plan_id;
            $user->expires = $expires;
            $user->save();
          }
        }
      }
    }
    return response()->json(['ok'], 200);
  }

  /**
   * Stripe webhook
   */
  public function postWebhook() {
    $sendMail = false;
    $sendUserMail = false;
    $stripe_secret_key = config('general.stripe_secret_key');
    if ($stripe_secret_key === null) {
      abort(404);
    }

    // Set Stripe secret key
    \Stripe\Stripe::setApiKey($stripe_secret_key);

    // Retrieve the request's body and parse it as JSON
    $input = @file_get_contents("php://input");
    $event_json = json_decode($input);

    // A customer is deleted in Stripe, set user to trial mode
    if ($event_json->type == 'customer.deleted') {
      $stripe_customer_id = $event_json->data->object->id;

      // Find matching user
      $user = \App\User::where('remote_customer_id', $stripe_customer_id)->first();

      if (! empty($user)) {
        $user->previous_remote_customer_id = $user->remote_customer_id;
        $user->remote_customer_id = null;
        $user->plan_id = null;
        $user->expires = Carbon::now()->addDays(config('system.trial_days'));
        $user->save();

        $sendMail = true;
        $subject = "Customer deleted in Stripe dashboard";
        $body_top = "Customer: " . $user->name . " (" . $user->email . ")" . PHP_EOL . PHP_EOL;
        $body_top .= "User ID: " . $user->id . PHP_EOL . PHP_EOL;
        $body_top .= "Stripe Customer ID: " . $stripe_customer_id . PHP_EOL . PHP_EOL;
        $body_bottom = "";

        $sendUserMail = true;
        $user_subject = "Subscription cancelled";
        $user_body_top = "Your subscription has been cancelled." . PHP_EOL . PHP_EOL;
        $user_body_bottom = "";
      }
    }

    // The payment has succeeded, update the expiration date
    if ($event_json->type == 'invoice.payment_succeeded') {
      $stripe_customer_id = $event_json->data->object->customer;  
      $product_id_stripe = $event_json->data->object->lines->data{0}->plan->id;

      // Find matching user
      $user = \App\User::where('remote_customer_id', $stripe_customer_id)->first();

      // Find matching plan
      $plan = \Platform\Models\Plan::where('product_id_stripe', $product_id_stripe)->first();

      if (! empty($user) && ! empty($plan)) {

        $expires = Carbon::now()->addDays(1);

        if (isset($event_json->data->object->lines->data{0}->plan->interval)) {
          $interval_count = (isset($event_json->data->object->lines->data{0}->plan->interval_count)) ? $event_json->data->object->lines->data{0}->plan->interval_count : 1;
          switch ($event_json->data->object->lines->data{0}->plan->interval) {
            case 'day': $expires = Carbon::now()->addDays($interval_count); break;
            case 'week': $expires = Carbon::now()->addWeeks($interval_count); break;
            case 'month': $expires = Carbon::now()->addMonths($interval_count); break;
            case 'year': $expires = Carbon::now()->addYears($interval_count); break;
          }
        }

        $user->plan_id = $plan->id;
        $user->expires = $expires;
        $user->save();

        $sendMail = true;
        $subject = "Payment complete";
        $body_top = "Customer: " . $user->name . " (" . $user->email . ")" . PHP_EOL . PHP_EOL;
        $body_top .= "Plan: " . $plan->name . PHP_EOL . PHP_EOL;
        $body_top .= "User ID: " . $user->id . PHP_EOL . PHP_EOL;
        $body_top .= "Stripe Customer ID: " . $stripe_customer_id . PHP_EOL . PHP_EOL;
        $body_bottom = "";

        $sendUserMail = true;
        $user_subject = "Payment complete";
        $user_body_top = "Thank you for your purchase! Your subscription has been updated." . PHP_EOL . PHP_EOL;
        $user_body_bottom = "";
      }
    }

    // A subscription is updated for a customer in Stripe
    if ($event_json->type == 'customer.subscription.updated') {
      $stripe_customer_id = $event_json->data->object->customer;
      $product_id_stripe = $event_json->data->object->plan->id;
      $status = $event_json->data->object->status; // https://stripe.com/docs/api/subscriptions/object#subscription_object-status

      if ($status == 'active') {
        // Find matching user
        $user = \App\User::where('remote_customer_id', $stripe_customer_id)->first();

        // Find matching plan
        $plan = \Platform\Models\Plan::where('product_id_stripe', $product_id_stripe)->first();

        if (! empty($user) && ! empty($plan)) {

          $expires = Carbon::now()->addMonths(1);

          if (isset($event_json->data->object->plan->interval)) {
            $interval_count = (isset($event_json->data->object->plan->interval_count)) ? $event_json->data->object->plan->interval_count : 1;
            switch ($event_json->data->object->plan->interval) {
              case 'day': $expires = Carbon::now()->addDays($interval_count); break;
              case 'week': $expires = Carbon::now()->addWeeks($interval_count); break;
              case 'month': $expires = Carbon::now()->addMonths($interval_count); break;
              case 'year': $expires = Carbon::now()->addYears($interval_count); break;
            }
          }

          $user->plan_id = $plan->id;
          $user->expires = $expires;
          $user->save();

          $sendMail = true;
          $subject = "Subscription updated";
          $body_top = "Customer: " . $user->name . " (" . $user->email . ")" . PHP_EOL . PHP_EOL;
          $body_top .= "Plan: " . $plan->name . PHP_EOL . PHP_EOL;
          $body_top .= "User ID: " . $user->id . PHP_EOL . PHP_EOL;
          $body_top .= "Stripe Customer ID: " . $stripe_customer_id . PHP_EOL . PHP_EOL;
          $body_bottom = "";

          $sendUserMail = true;
          $user_subject = "Subscription updated";
          $user_body_top = "Your subscription has been successfully updated." . PHP_EOL . PHP_EOL;
          $user_body_bottom = "";
        }
      } elseif($status == 'unpaid' || $status == 'past_due') {
        // Find matching user
        $user = \App\User::where('remote_customer_id', $stripe_customer_id)->first();

        if (! empty($user)) {
          $user->plan_id = null;
          $user->expires = Carbon::now()->addDays(config('system.grace_period_days'));
          $user->save();

          $sendMail = true;
          $subject = "Subscription payment failed or past due";
          $body_top = "Customer: " . $user->name . " (" . $user->email . ")" . PHP_EOL . PHP_EOL;
          $body_top .= "Plan: " . $plan->name . PHP_EOL . PHP_EOL;
          $body_top .= "User ID: " . $user->id . PHP_EOL . PHP_EOL;
          $body_top .= "Stripe Customer ID: " . $stripe_customer_id . PHP_EOL . PHP_EOL;
          $body_bottom = "";

          $sendUserMail = true;
          $user_subject = "Subscription payment failed";
          $user_body_top = "Your payment has failed or is past due." . PHP_EOL . PHP_EOL;
          $user_body_bottom = "";
        }
      }
    }

    // A subscription is deleted for a customer in Stripe, set user to trial mode
    // Deprecated for now because users can end subscription from system, and will keep active account until plan expires
    /*
    if ($event_json->type == 'customer.subscription.deleted') {
      $stripe_customer_id = $event_json->data->object->customer;

      // Find matching user
      $user = \App\User::where('remote_customer_id', $stripe_customer_id)->first();

      if (! empty($user)) {
        $user->plan_id = null;
        $user->expires = Carbon::now()->addDays(config('system.trial_days'));
        $user->save();
      }
    }
    */

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

  /**
   * Cancel subscription
   */
  public function postCancelSubscription() {
    $sendMail = false;
    $sendUserMail = false;
    $user = auth()->user();

    if ($user->remote_customer_id !== null) {
      // Get Stripe key
      $stripe_secret_key = config('general.stripe_secret_key');
      if ($stripe_secret_key === null) {
        return response()->json(['msg' => 'Stripe key not configured.']);
      }

      // Set Stripe secret key
      \Stripe\Stripe::setApiKey($stripe_secret_key);

      $customer = \Stripe\Customer::retrieve($user->remote_customer_id);

      $subscription = (isset($customer->subscriptions->data[0])) ? $customer->subscriptions->data[0] : null;

      if ($subscription !== null) {
        // Find matching plan
        $plan = \Platform\Models\Plan::where('id', $user->plan_id)->first();

        $subscription = \Stripe\Subscription::retrieve($subscription->id);
        $subscription->cancel();
        $user->previous_remote_customer_id = $user->remote_customer_id;
        $user->remote_customer_id = null;
        $user->save();

        $sendMail = true;
        $subject = "Subscription cancelled";
        $body_top = "Customer: " . $user->name . " (" . $user->email . ")" . PHP_EOL . PHP_EOL;
        $body_top .= "Plan: " . $plan->name . PHP_EOL . PHP_EOL;
        $body_top .= "User ID: " . $user->id . PHP_EOL . PHP_EOL;
        $body_top .= "Stripe Customer ID: " . $user->remote_customer_id . PHP_EOL . PHP_EOL;
        $body_bottom = "";

        $sendUserMail = true;
        $user_subject = "Subscription cancelled";
        $user_body_top = "Your subscription has been cancelled successfully." . PHP_EOL . PHP_EOL;
        $user_body_bottom = "";

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

      } else {
        return response()->json(['msg' => 'User has no active subscription.']);
      }

      return response()->json(true);
    } else {
      return response()->json(['msg' => 'User has no remote customer id.']);
    }
  }
}