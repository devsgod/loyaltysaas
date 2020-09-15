<?php

namespace Platform\Controllers\Staff;

use App\Staff;
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

      if ($token = $this->guard()->attempt($credentials, $remember)) {
        // Login correct, check if staff member has permission to this campaign/business
        $hasPermission = auth('staff')->user()->businesses->contains($campaign->business_id);

        if ($hasPermission) {
            auth('staff')->user()->logins = auth('staff')->user()->logins + 1;
            auth('staff')->user()->last_login_ip_address =  request()->ip();
            auth('staff')->user()->last_login = Carbon::now('UTC');
            auth('staff')->user()->save();

            return response()->json(['status' => 'success'], 200)->header('Authorization', $token);
        } else {
          $this->guard()->logout();
          return response()->json(['error' => 'login_error'], 401);
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

      $user = Staff::withoutGlobalScopes()
        ->where('email', $request->email)
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
        $email->app_url = $campaign->url . '/staff';
        $email->from_name = $campaign->business->name;
        $email->from_email = ($campaign->business->email != null) ? $campaign->business->email : config('general.mail_address_from');

        $email->to_name = $user->name;
        $email->to_email = $user->email;
        $email->subject = trans('app.reset_password_mail_subject');
        $email->body_top = trans('app.reset_password_mail_top');
        $email->cta_label = trans('app.reset_password_mail_cta');
        $email->cta_url = $campaign->url . '/staff/password/reset/' . $token;
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

        $user = Staff::withoutGlobalScopes()->where('email', $password_reset->email)
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
        'email' => ['required', 'email', 'max:64', Rule::unique('staff')->where(function ($query) use ($account) {
            return $query->where('account_id', $account->id)->where('id', '<>', auth('staff')->user()->id);
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
      if (! Hash::check($request->current_password, auth('staff')->user()->password)) {
        return response()->json([
          'status' => 'error',
          'errors' => ['current_password' => [trans('app.current_password_incorrect')]]
        ], 422);
      }

      // All good, update profile
      auth('staff')->user()->name = $request->name;
      auth('staff')->user()->email = $request->email;
      auth('staff')->user()->locale = $request->locale;
      auth('staff')->user()->timezone = $request->timezone;

      // Update password
      if ($request->new_password !== null && $request->new_password != 'null') auth('staff')->user()->password = bcrypt($request->new_password);

      auth('staff')->user()->save();

      // Update avatar
      if (json_decode($request->avatar_media_changed) === true) {
        $file = $request->file('avatar');
        if ($file !== null) {
          auth('staff')->user()
            ->addMedia($file)
            ->sanitizingFileName(function($fileName) {
              return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
            })
            ->toMediaCollection('avatar', 'media');
        } else {
          auth('staff')->user()
            ->clearMediaCollection('avatar');
        }
      }

      return response()->json([
        'status' => 'success',
        'user' => auth('staff')->user()
      ]);
    }

    /**
     * Get user info.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function user(Request $request) {
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();
      $user = Staff::withoutGlobalScopes()->where('id', Auth::user('staff')->id)->where('active', 1)->firstOrFail();

      // Login correct, check if staff member has permission to this campaign/business
      $hasPermission = $user->businesses->contains($campaign->business_id);

      if (! $hasPermission) {
        $this->guard()->logout();
        return response()->json([
          'status' => 'error'
        ], 422);
      }

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
      return Auth::guard('staff');
    }
}