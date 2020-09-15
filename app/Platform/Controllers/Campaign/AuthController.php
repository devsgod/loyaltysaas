<?php

namespace Platform\Controllers\Campaign;

use App\Customer;
use Platform\Models\History;
use Platform\Controllers\Core;
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

class AuthController extends \App\Http\Controllers\Controller
{
    /*
    |--------------------------------------------------------------------------
    | Authorization Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling authentication related
    | features like registration, login, logout and password reset.
    | It's designed for /api/ use with JSON responses.
    |
    */

    /**
     * Handle user registration.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function register(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();

      $v = Validator::make($request->all(), [
        'name' => 'required|min:2|max:32',
        'email' => ['required', 'email', 'max:64', Rule::unique('customers')->where(function ($query) use ($campaign) {
            return $query->where('campaign_id', $campaign->id);
          })
        ],
        'password' => 'required|min:8|max:24',
        'terms' => 'accepted'
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      // Check if account limitations are reached
      $max = $campaign->user->plan_limitations['customers'];
      $count = count($campaign->user->customers);

      if ($count > $max && ! env('APP_DEMO', false)) {
        $email = new \stdClass;
        $email->app_name = $account->app_name;
        $email->app_url = '//' . $account->app_host;
        $email->from_name = $account->app_mail_name_from;
        $email->from_email = $account->app_mail_address_from;
        $email->to_name = $campaign->user->name;
        $email->to_email = $campaign->user->email;
        $email->subject = "Account limitation reached";
        $email->body_top = "A user (" . $request->email . ") could not sign up on https:" . $campaign->url . " because the maximum amount of customers (" . $max . ") has been reached.";
        $email->cta_label = "Upgrade account";
        $email->cta_url = '//' . $account->app_host . '/go#/billing';
        $email->body_bottom = "Update your subscription to allow more customers to sign up.";

        Mail::send(new \App\Mail\SendMail($email));

        return response()->json([
          'status' => 'error',
          'error' => "limitation_reached"
        ], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $language = ($request->language !== null) ? $request->language : config('system.default_language');
      $timezone = ($request->timezone !== null) ? $request->timezone : config('system.default_timezone');
 
      $verification_code = Str::random(32);

      $customer_number = Core\Secure::getRandom(9, '1234567890');

      $user = new Customer;
      $user->account_id = $account->id;
      $user->campaign_id = $campaign->id;
      $user->created_by = $campaign->created_by;
      $user->role = 1;
      $user->active = 1;
      $user->customer_number = $customer_number;
      $user->name = $request->name;
      $user->email = $request->email;
      $user->password = bcrypt($request->password);
      $user->language = $language;
      $user->locale = $locale;
      $user->timezone = $timezone;
      $user->signup_ip_address = request()->ip();
      $user->verification_code = $verification_code;
      $user->save();

      $this->ensureNumberIsUnique($user);

      // Add points for signing up
      if ($campaign->signup_bonus_points > 0) {
        $history = new History;

        $history->customer_id = $user->id;
        $history->campaign_id = $campaign->id;
        $history->created_by = $campaign->created_by;
        $history->event = 'Sign up bonus';
        $history->points = $campaign->signup_bonus_points;
        $history->save();
      }

      return response()->json(['status' => 'success'], 200);
    }

    /**
     * Make sure customer number is unique
     *
     * @return boolean
     */
    public function ensureNumberIsUnique(Customer $customer) {
        $user = Customer::where('id', '<>', $customer->id)->where('created_by', $customer->created_by)->where('customer_number', $customer->customer_number)->first();
        if ($user === null) {
          return true;
        } else {
          $customer_number = Core\Secure::getRandom(9, '1234567890');
          $customer->customer_number = $customer_number;
          $customer->save();
          $this->ensureNumberIsUnique($customer);
        }
    }
  
    /**
     * Handle user login.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function login(Request $request) {
      $v = Validator::make($request->all(), [
        'email' => 'required|email|max:64',
        'password' => 'required|min:6|max:24'
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      $remember = (bool) $request->get('remember', false);

      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();

      $credentials = $request->only('email', 'password');
      $credentials['active'] = 1;
      $credentials['account_id'] = $account->id;
      $credentials['campaign_id'] = $campaign->id;

      if ($token = $this->guard()->attempt($credentials, $remember)) {
        auth('customer')->user()->logins = auth('customer')->user()->logins + 1;
        auth('customer')->user()->last_login_ip_address =  request()->ip();
        auth('customer')->user()->last_login = Carbon::now('UTC');
        auth('customer')->user()->save();
/*
        $history = new History;

        $history->customer_id = auth('customer')->user()->id;
        $history->campaign_id = $campaign->id;
        $history->created_by = $campaign->created_by;
        $history->event = 'Sign in bonus';
        $history->points = 100;
        $history->save();
*/
        return response()->json(['status' => 'success'], 200)->header('Authorization', $token);
      }
      return response()->json(['error' => 'login_error'], 401);
    }

    /**
     * Handle user logout.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function logout() {
      $this->guard()->logout();
      return response()->json([
        'status' => 'success',
        'msg' => 'Logged out successfully.'
      ], 200);
    }

    /**
     * Refresh authorization token.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function refresh() {
      try {
        $token = $this->guard()->refresh();
      }
      catch (\Exception $e) {
        return response()->json(['error' => 'refresh_token_error'], 401);
      }

      return response()
        ->json(['status' => 'successs'], 200)
        ->header('Authorization', $token);
    }

    /**
     * Send a password reset email.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function passwordReset(Request $request) {
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();

      $v = Validator::make($request->all(), [
        'email' => 'required|email|max:64'
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $user = Customer::withoutGlobalScopes()
        ->where('email', $request->email)
        ->where('campaign_id', $campaign->id)
        ->where('active', 1)
        ->first();

      if ($user !== null) {

        $token = Str::random(32);

        DB::table('password_resets')
          ->where('email', $user->email)
          ->delete();

        DB::table('password_resets')->insert(
          ['email' => $user->email, 'token' => $token, 'created_at' => Carbon::now('UTC')]
        );

        $email = new \stdClass;

        $email->app_name = $campaign->name;
        $email->app_url = $campaign->url;
        $email->from_name = $campaign->business->name;
        $email->from_email = ($campaign->business->email != null) ? $campaign->business->email : config('general.mail_address_from');

        $email->to_name = $user->name;
        $email->to_email = $user->email;
        $email->subject = trans('campaign.reset_password_mail_subject');
        $email->body_top = trans('campaign.reset_password_mail_top');
        $email->cta_label = trans('campaign.reset_password_mail_cta');
        $email->cta_url = $campaign->url . '/password/reset/' . $token;
        $email->body_bottom = trans('campaign.reset_password_mail_bottom');

        Mail::send(new \App\Mail\SendMail($email));

      } else {
        return response()->json([
          'status' => 'error',
          'error' => trans('passwords.user')
        ], 200);
      }

      return response()->json([
        'status' => 'success'
      ], 200);
    }

    /**
     * Validate reset password token.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function passwordResetValidateToken(Request $request) {
      $v = Validator::make($request->all(), [
        'token' => 'required|min:32|max:32'
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $password_reset = DB::table('password_resets')
        ->select('email')
        ->where('token', $request->token)
        ->where('created_at', '>=', \Carbon\Carbon::now()->addHour(-24)->toDateTimeString())
        ->first();

      if ($password_reset !== null) {
        return response()->json([
          'status' => 'success'
        ], 200);
      } else {
        return response()->json([
          'status' => 'error',
          'error' => 'invalid_token'
        ], 200);
      }
    }

    /**
     * Update password.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function passwordUpdate(Request $request) {
      $v = Validator::make($request->all(), [
        'token' => 'required|min:32|max:32',
        'password' => 'required|min:8|max:24'
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $password_reset = DB::table('password_resets')
        ->select('email')
        ->where('token', $request->token)
        ->where('created_at', '>=', \Carbon\Carbon::now()->addHour(-24)->toDateTimeString())
        ->first();

      if ($password_reset !== null) {

        DB::table('password_resets')->where('token', $request->token)->delete();

        $user = Customer::withoutGlobalScopes()->where('email', $password_reset->email)
          ->where('active', 1)
          ->first();

        if ($user !== null) {

          $user->password = bcrypt($request->password);
          $user->save();

          return response()->json([
            'status' => 'success'
          ], 200);
        }
      } else {
        return response()->json([
          'status' => 'error',
          'error' => 'invalid_token'
        ], 200);
      }
    }

    /**
     * Update profile.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function postUpdateProfile(Request $request) {
      $account = app()->make('account');
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $v = Validator::make($request->all(), [
        'current_password' => 'required|min:8|max:24',
        'name' => 'required|min:2|max:32',
        'email' => ['required', 'email', 'max:64', Rule::unique('customers')->where(function ($query) use ($campaign) {
            return $query->where('campaign_id', $campaign->id)->where('id', '<>', auth('customer')->user()->id);
          })
        ],
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      // Verify password
      if (! Hash::check($request->current_password, auth('customer')->user()->password)) {
        return response()->json([
          'status' => 'error',
          'errors' => ['current_password' => [trans('campaign.current_password_incorrect')]]
        ], 422);
      }

      // All good, update profile
      auth('customer')->user()->name = $request->name;
      auth('customer')->user()->email = $request->email;
      auth('customer')->user()->locale = $request->locale;
      auth('customer')->user()->timezone = $request->timezone;

      // Update password
      if ($request->new_password !== null && $request->new_password != 'null') auth('customer')->user()->password = bcrypt($request->new_password);

      auth('customer')->user()->save();

      // Update avatar
      if (json_decode($request->avatar_media_changed) === true) {
        $file = $request->file('avatar');
        if ($file !== null) {
          auth('customer')->user()
            ->addMedia($file)
            ->sanitizingFileName(function($fileName) {
              return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
            })
            ->toMediaCollection('avatar', 'media');
        } else {
          auth('customer')->user()
            ->clearMediaCollection('avatar');
        }
      }

      return response()->json([
        'status' => 'success',
        'user' => auth('customer')->user()
      ]);
    }
  
    /**
     * Get user info.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function user(Request $request) {
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();
      $user = Customer::withoutGlobalScopes()->where('id', Auth::user('customer')->id)->where('active', 1)->where('campaign_id', $campaign->id)->firstOrFail();

      $user->touch();

      $user->timezone = $user->getTimezone();
      $user->language = $user->getLanguage();
      $user->locale = $user->getLocale();
      $user->currency = $user->getCurrency();

      $return = [
        'uuid' => $user->uuid,
        'active' => (int) $user->active,
        'avatar' => $user->avatar,
        'name' => $user->name,
        'email' => $user->email,
        'number' => $user->number,
        'points' => (int) $user->points,
        'role' => (int) $user->role,
        'language' => $user->language,
        'locale' => $user->locale,
        'timezone' => $user->timezone,
        'currency' => $user->currency
      ];

      return response()->json([
        'status' => 'success',
        'data' => $return
      ]);
    }

    /**
     * Get guard for logged in user.
     *
     * @return \Illuminate\Support\Facades\Auth 
     */
    private function guard() {
      return Auth::guard('customer');
    }
}