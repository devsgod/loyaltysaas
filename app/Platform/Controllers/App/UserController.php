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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends \App\Http\Controllers\Controller
{
    /*
    |--------------------------------------------------------------------------
    | User Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling user features which are
    | not authorization-related.
    | It's designed for /api/ use with JSON responses.
    |
    */

    /**
     * Get user stats.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getStats(Request $request) {
      $stats = auth()->user()->getUserStats();
      return response()->json($stats, 200);
    }

    /**
     * Get user plans.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getPlans(Request $request) {
        $account = app()->make('account');
        $plans = \Platform\Models\Plan::getPlansForBilling(3);
        $user = auth()->user();
        $remote_customer = ($user->remote_customer_id === null) ? false : true;

        $vendor_id = null;
        if (config('general.payment_provider') == 'paddle') $vendor_id = config('general.paddle_vendor_id');
        if (config('general.payment_provider') == '2checkout') $vendor_id = config('general.2checkout_vendor_id');
        if (config('general.payment_provider') == 'stripe') $vendor_id = config('general.stripe_public_key');

        $response = [
          'app_contact' => $account['app_contact'],
          'payment_provider' => (config('general.payment_provider') == '') ? null : config('general.payment_provider'),
          'payment_test_mode' => config('general.payment_test_mode'),
          'vendor_id' => $vendor_id,
          'affiliate_id' => $account->app_vendor_id,
          'remote_customer' => $remote_customer,
          'plans' => $plans,
          'subscription' => [
            'subscription_cancel_url' => $user->meta['subscription_cancel_url'] ?? null,
            'subscription_receipt_url' => $user->meta['subscription_receipt_url'] ?? null
          ]
        ];

        return response()->json($response, 200);
    }
}