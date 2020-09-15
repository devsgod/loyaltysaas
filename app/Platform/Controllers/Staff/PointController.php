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

class PointController extends Controller {

    /*
     |--------------------------------------------------------------------------
     | Points related functions
     |--------------------------------------------------------------------------
     */

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
      $code = Code::where('code', $token)->where('campaign_id', $campaign->id)->where('type', 'token')->where('expires_at', '>', \Carbon\Carbon::now())->first();

      $tokenIsValid = false;
      $customer = null;

      if ($code !== null) {
        $tokenIsValid = true;
        $customer = $code->customer;
      }

      return response()->json([
        'status' => 'success',
        'tokenIsValid' => $tokenIsValid,
        'customer' => $customer
      ], 200);
    }

    /**
     * Push credited points to broadcast channel
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function postPushCreditsByToken(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $token = $request->token;
      $points = $request->points;
      $segments = $request->segments;

      // Validate token
      $code = Code::where('code', $token)->where('campaign_id', $campaign->id)->where('type', 'token')->where('expires_at', '>', \Carbon\Carbon::now())->first();

      if ($code !== null) {

        $history = new History;
        $history->customer_id = $code->customer_id;
        $history->campaign_id = $campaign->id;
        $history->staff_id = auth('staff')->user()->id;
        $history->staff_name = auth('staff')->user()->name;
        $history->staff_email = auth('staff')->user()->email;
        $history->points = $points;
        $history->event = 'Credited with QR code';
        $history->created_by = $campaign->created_by;

        $history->save();

        // Segments
        if (is_array($segments) && count($segments) > 0) {
          $history->segments()->sync($segments);
        } 

        // Delete
        $code->delete();

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

        $data = ['points' => $points];
        $pusher->trigger($code->code, 'credited', $data);

        return response()->json([
          'status' => 'success'
        ], 200);
      }
    }

    /**
     * Generate easy to remember code for merchant
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGenerateCustomerCode(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $points = $request->points;
      $expires = $request->expires;
      $segments = $request->segments;

      // First, delete existing codes
      $deletedRows = Code::where('type', 'customer')->where('expires_at', '<', \Carbon\Carbon::now())->delete();
      //$deletedRows = Code::where('campaign_id', $campaign->id)->where('type', 'customer')->where('staff_id', auth('staff')->user()->id)->delete();

      // Create new code
      $customer_code = $this->getUniqueCode('customer_code', $campaign->id);

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
      $code->type = 'customer';
      $code->code = $customer_code;
      $code->points = $points;
      $code->expires_at = $expires_at;
      $code->created_by = $campaign->created_by;

      $code->save();

      // Segments
      if (is_array($segments) && count($segments) > 0) {
        $code->segments()->sync($segments);
      } 

      // Format code
      $customer_code = implode('-', str_split($customer_code, 3));

      return response()->json([
        'status' => 'success',
        'code' => $customer_code
      ], 200);
    }

    /**
     * Get active customer code(s)
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getActiveCustomerCodes(Request $request) {
      $codes = Code::select('uuid', 'code', 'expires_at', 'points')->where('type', 'customer')->where('staff_id', auth('staff')->user()->id)->where('expires_at', '>', \Carbon\Carbon::now())->orderBy('expires_at', 'asc')->get();

      $codes = $codes->map(function ($item) {
        if (isset($item['expires_at']) && $item['expires_at'] != null) {
          $item['expires_at']  = Carbon::parse($item['expires_at'], config('app.timezone'))->setTimezone(auth('staff')->user()->getTimezone());
        }
        return $item;
      });

      return response()->json($codes, 200);
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
      if ($type == 'customer_code') {
        $customer_code = Core\Secure::getRandom(9, '1234567890000');
        $code_type = 'customer';
      } elseif ($type == 'merchant_code') {
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
     * Credit customer by number
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postCreditCustomer(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $points = $request->points;
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

      $history = new History;
      $history->customer_id = $customer->id;
      $history->campaign_id = $campaign->id;
      $history->staff_id = auth('staff')->user()->id;
      $history->staff_name = auth('staff')->user()->name;
      $history->staff_email = auth('staff')->user()->email;
      $history->points = $points;
      $history->event = 'Credited by staff member';
      $history->created_by = $campaign->created_by;

      $history->save();

      // Segments
      if (is_array($segments) && count($segments) > 0) {
        $history->segments()->sync($segments);
      } 

      return response()->json([
        'status' => 'success'
      ], 200);
    }
}