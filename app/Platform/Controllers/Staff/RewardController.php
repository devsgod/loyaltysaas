<?php 

namespace Platform\Controllers\Staff;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Staff;
use App\Customer;
use Platform\Models\Code;
use Platform\Models\History;
use App\Http\Controllers;
use Platform\Controllers\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Barzo\Password\Generator;

class RewardController extends Controller {

    /*
     |--------------------------------------------------------------------------
     | Rewards related functions
     |--------------------------------------------------------------------------
     */

    /**
     * Get all rewards from campaign
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRewards() {
        $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
        $rewards = $campaign->getAciveRewards()->pluck('title_with_points', 'uuid');

        return response()->json($rewards, 200);
    }

    /**
     * Validate if link token is (still) valid
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function postValidateLinkToken(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $token = $request->token;

      // Validate token
      $code = Code::where('code', $token)->where('campaign_id', $campaign->id)->where('type', 'reward_token')->where('expires_at', '>', \Carbon\Carbon::now())->first();

      $tokenIsValid = false;
      $customer = null;
      $reward = null;

      if ($code !== null) {
        $tokenIsValid = true;
        $customer = $code->customer;
        $reward = $code->reward->uuid;
      }

      return response()->json([
        'status' => 'success',
        'tokenIsValid' => $tokenIsValid,
        'customer' => $customer,
        'reward' => $reward
      ], 200);
    }

    /**
     * Push redeemed reward to broadcast channel
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function postRedeemRewardByToken(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $token = $request->token;
      $reward = $request->reward;
      $reward = \Platform\Models\Reward::whereUuid(request('reward', 0))->firstOrFail();
      $segments = $request->segments;

      // Validate token
      $code = Code::where('code', $token)->where('campaign_id', $campaign->id)->where('type', 'reward_token')->where('expires_at', '>', \Carbon\Carbon::now())->first();

      // Verify if customer has enough points
      $new_points_customer = $code->customer->points - $reward->points_cost;

      if ($new_points_customer < 0) {
        return response()->json([
          'status' => 'error',
          'errors' => ['reward' => 'Customer does not have enough points for this reward']
        ], 422);
      }

      if ($code !== null) {

        $history = new History;
        $history->customer_id = $code->customer_id;
        $history->campaign_id = $campaign->id;
        $history->staff_id = auth('staff')->user()->id;
        $history->staff_name = auth('staff')->user()->name;
        $history->staff_email = auth('staff')->user()->email;
        $history->reward_id = $reward->id;
        $history->reward_title = $reward->title;
        $history->points = -$reward->points_cost;
        $history->event = 'Redeemed with QR code';
        $history->created_by = $campaign->created_by;

        $history->save();

        // Segments
        if (is_array($segments) && count($segments) > 0) {
          $history->segments()->sync($segments);
        } 

        // Delete
        $code->delete();

        // Increment reward redemptions
        $reward->increment('number_of_times_redeemed');

        // Push credited
        $options = array(
          'cluster' => config('broadcasting.connections.pusher.options.cluster'),
          'useTLS' => true
        );

        $pusher = new \Pusher\Pusher(
          config('broadcasting.connections.pusher.key'),
          config('broadcasting.connections.pusher.secret'),
          config('broadcasting.connections.pusher.app_id'),
          $options
        );

        $data = [
          'reward' => $reward->title_with_points,
          'points' => $new_points_customer
        ];
        $pusher->trigger($code->code, 'redeemed', $data);

        return response()->json([
          'status' => 'success',
          'points' => $new_points_customer
        ], 200);
      }
    }

    /**
     * Generate easy to remember code for merchant
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGenerateMerchantCode(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $expires = $request->expires;

      // First, delete existing codes
      $deletedRows = Code::where('type', 'merchant')->where('staff_id', auth('staff')->user()->id)->delete();

      // Create new code
      $merchant_code = $this->getUniqueCode('merchant_code', $campaign->id);

      switch ($expires) {
        case 'hour': $expires_at = Carbon::now()->addHours(1); break;
        case 'day': $expires_at = Carbon::now()->addDays(1); break;
        case 'week': $expires_at = Carbon::now()->addWeeks(1); break;
        case 'month': $expires_at = Carbon::now()->addMonths(1); break;
      }

      $code = new Code;
      $code->campaign_id = $campaign->id;
      $code->staff_id = auth('staff')->user()->id;
      $code->staff_name = auth('staff')->user()->name;
      $code->staff_email = auth('staff')->user()->email;
      $code->type = 'merchant';
      $code->code = $merchant_code;
      $code->expires_at = $expires_at;
      $code->created_by = $campaign->created_by;

      $code->save();

      return response()->json([
        'status' => 'success',
        'code' => $merchant_code
      ], 200);
    }

    /**
     * Get active merchant code
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getActiveMerchantCode(Request $request) {
      $code = Code::select('uuid', 'code', 'expires_at')->where('type', 'merchant')->where('staff_id', auth('staff')->user()->id)->where('expires_at', '>', \Carbon\Carbon::now())->first();

      if ($code !== null && $code->expires_at !== null) $code->expires_at = Carbon::parse($code->expires_at, config('app.timezone'))->setTimezone(auth('staff')->user()->getTimezone());

      return response()->json($code, 200);
    }

    /**
     * Make sure code is unique
     *
     * @return boolean
     */
    public function getUniqueCode($type, $campaign_id) {
      if ($type == 'merchant_code') {
        $customer_code = Generator::generateEn(2);
        $code_type = 'merchant';
      }

      $code = Code::where('campaign_id', $campaign_id)->where('code', $customer_code)->where('type', $code_type)->first();

      if ($code === null) {
        return $customer_code;
      } else {
        return $this->getUniqueCode($type, $campaign_id);
      }
    }

    /**
     * Redeem reward with customer number
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postRedeemReward(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $customerNumber = $request->number;
      $segments = $request->segments;

      // Find customer by number
      $customer = Customer::where('active', 1)->where('campaign_id', $campaign->id)->where('customer_number', $customerNumber)->first();

      if ($customer === null) {
        return response()->json([
          'status' => 'error',
          'errors' => ['number' => 'Customer not found']
        ], 422);
      }

      $reward = $request->reward;
      $reward = \Platform\Models\Reward::whereUuid(request('reward', 0))->firstOrFail();

      // Check if customer has sufficient balance
      if ($reward->points_cost > $customer->points) {
        return response()->json([
          'status' => 'error',
          'errors' => ['reward' => 'Customer does not have enough points']
        ], 422);
      }

      $history = new History;
      $history->customer_id = $customer->id;
      $history->campaign_id = $campaign->id;
      $history->staff_id = auth('staff')->user()->id;
      $history->staff_name = auth('staff')->user()->name;
      $history->staff_email = auth('staff')->user()->email;
      $history->reward_id = $reward->id;
      $history->reward_title = $reward->title;
      $history->points = -$reward->points_cost;
      $history->event = 'Redeemed with customer number';
      $history->created_by = $campaign->created_by;

      $history->save();

      // Segments
      if (is_array($segments) && count($segments) > 0) {
        $history->segments()->sync($segments);
      }

      // Increment reward redemptions
      $reward->increment('number_of_times_redeemed');

      return response()->json([
        'status' => 'success'
      ], 200);
    }
}