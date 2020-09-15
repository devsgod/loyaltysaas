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
      $v = Validator::make($request->all(), [
        'name' => 'required|min:2|max:32',
        'email' => 'required|email|max:64|unique:users',
        'password' => 'required|min:8|max:24',
        'terms' => 'accepted',
      ]);

      if ($v->fails()) {
        return response()->json([
          'status' => 'error',
          'errors' => $v->errors()
        ], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $account = app()->make('account');
      $language = ($request->language !== null) ? $request->language : config('system.default_language');
      $timezone = ($request->timezone !== null) ? $request->timezone : config('system.default_timezone');

      $currency = config('system.default_currency');

      // Detect currency based on locale
      if (false !== setlocale(LC_ALL, $locale)) {
        $locale_info = localeconv();
        $currency = $locale_info['int_curr_symbol'];
      }

      $verification_code = Str::random(32);
      $trial_days = config('system.trial_days'); 

      $user = new User;
      $user->account_id = $account->id;
      $user->created_by = $account->id;
      $user->role = 3;
      $user->active = 1;
      $user->name = $request->name;
      $user->email = $request->email;
      $user->password = bcrypt($request->password);
      $user->language = $language;
      $user->locale = $locale;
      $user->timezone = $timezone;
      $user->currency_code = $currency;
      $user->expires = Carbon::now()->addDays($trial_days);
      $user->signup_ip_address = request()->ip();
      $user->verification_code = $verification_code;
      $user->save();
      return response()->json(['status' => 'success'], 200);
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

      $credentials = $request->only('email', 'password');
      $credentials['active'] = 1;

      if ($token = $this->guard()->attempt($credentials, $remember)) {
        auth()->user()->logins = auth()->user()->logins + 1;
        auth()->user()->last_login_ip_address =  request()->ip();
        auth()->user()->last_login = Carbon::now('UTC');
        auth()->user()->save();

        return response()->json(['status' => 'success'], 200)->header('Authorization', $token);
      }
      return response()->json(['error' => 'login_error'], 401);
    }

    /**
     * Handle impersonate login.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function impersonate(Request $request) {
      $uuid = request('uuid', null);

      if (auth()->user()->role == 1) {
        $user = User::withoutGlobalScopes()->whereUuid($uuid)->firstOrFail();
        if ($token = $this->guard()->login($user)) {
          return response()->json(['status' => 'success'], 200)->header('Authorization', $token);
        }
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

      $user = User::withoutGlobalScopes()->where('email', $request->email)
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
        $email->to_name = $user->name;
        $email->to_email = $user->email;
        $email->subject = trans('app.reset_password_mail_subject');
        $email->body_top = trans('app.reset_password_mail_top');
        $email->cta_label = trans('app.reset_password_mail_cta');
        $email->cta_url = url('go#/password/reset/' . $token);
        $email->body_bottom = trans('app.reset_password_mail_bottom');

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

        $user = User::withoutGlobalScopes()->where('email', $password_reset->email)
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

      if (env('APP_DEMO', false) === true && (auth()->user()->id == 1 || auth()->user()->id == 2)) {
        return response()->json(['status' => 'error', 'error' => 'demo'], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $v = Validator::make($request->all(), [
        'current_password' => 'required|min:8|max:24',
        'name' => 'required|min:2|max:32',
        'email' => ['required', 'email', 'max:64', Rule::unique('users')->where(function ($query) use ($account) {
            return $query->where('account_id', $account->id)->where('id', '<>', auth()->user()->id);
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
      if (! Hash::check($request->current_password, auth()->user()->password)) {
        return response()->json([
          'status' => 'error',
          'errors' => ['current_password' => [trans('app.current_password_incorrect')]]
        ], 422);
      }

      // All good, update profile
      auth()->user()->name = $request->name;
      auth()->user()->email = $request->email;
      auth()->user()->timezone = $request->timezone;
      auth()->user()->locale = $request->locale;
      auth()->user()->currency_code = $request->currency;
      auth()->user()->language = explode('_', $request->locale)[0];

      // Update password
      if ($request->new_password !== null && $request->new_password != 'null') auth()->user()->password = bcrypt($request->new_password);

      auth()->user()->save();

      // Update avatar
      if (json_decode($request->avatar_media_changed) === true) {
        $file = $request->file('avatar');
        if ($file !== null) {
          auth()->user()
            ->addMedia($file)
            ->sanitizingFileName(function($fileName) {
              return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
            })
            ->toMediaCollection('avatar', 'media');
        } else {
          auth()->user()
            ->clearMediaCollection('avatar');
        }
      }

      return response()->json([
        'status' => 'success',
        'user' => $this->user(false)
      ], 200);
    }

    /**
     * Get user info.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function user($returnResponse = true) {
      $user = User::withoutGlobalScopes()->where('id', Auth::user()->id)->where('active', 1)->firstOrFail();

      $user->timezone = $user->getTimezone();
      $user->language = $user->getLanguage();
      $user->locale = $user->getLocale();
      $user->currency = $user->getCurrency();
      $user->customer_count = $user->customers->count();

      $return = [
        'uuid' => $user->uuid,
        'active' => (int) $user->active,
        'demo' => (int) $user->demo,
        'avatar' => $user->avatar,
        'customer_count' => $user->customer_count,
        'name' => $user->name,
        'email' => $user->email,
        'plan_name' => $user->plan_name,
        'plan_id' => $user->plan_id,
        'role' => (int) $user->role,
        'expires_at' => $user->expires_at,
        'expired' => ($user->expires === null) ? false : $user->expires->isPast(),
        'language' => $user->language,
        'locale' => $user->locale,
        'timezone' => $user->timezone,
        'currency' => $user->currency
      ];

      if ($returnResponse) {
        return response()->json([
          'status' => 'success',
          'data' => $return
        ], 200);
      } else {
        return $return;
      }
    }

    /**
     * Get guard for logged in user.
     *
     * @return \Illuminate\Support\Facades\Auth 
     */
    private function guard() {
      return Auth::guard('api');
    }
}