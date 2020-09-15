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

class AdminController extends \App\Http\Controllers\Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    */

    /**
     * Get admin stats.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getStats(Request $request) {
        $stats = auth()->user()->getAdminStats();
        return response()->json($stats, 200);
    }

    /**
     * Get branding data.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getBranding(Request $request) {
        $user = auth()->user();

        $branding = [
          'payment_provider' => config('general.payment_provider'),
          'payment_test_mode' => config('general.payment_test_mode'),
          'app_name' => $user->app_name,
          'app_contact' => $user->app_contact,
          'app_mail_name_from' => $user->app_mail_name_from,
          'app_mail_address_from' => $user->app_mail_address_from,
          'app_host' => $user->app_host,
          'account_host' => config('general.cname_domain')
        ];

        return response()->json($branding, 200);
    }

    /**
     * Save settings > branding.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function postUpdateBranding(Request $request) {
        if (auth()->user()->app_demo == 1) return;

        if (env('APP_DEMO', false) === true && (auth()->user()->id == 1 || auth()->user()->id == 2)) {
          return;
        }

        $app_name = $request->app_name;
        $app_contact = $request->app_contact;
        $app_mail_address_from = $request->app_mail_address_from;
        $app_mail_name_from = $request->app_mail_name_from;
        $app_host = $request->domain;

        $validDomain = $this->validateDomain($app_host);
        if ($validDomain !== true) {
          return $validDomain;
        }

        // Validate
        $v = Validator::make($request->all(), [
            'app_name' => 'required|min:3|max:64',
            'app_contact' => 'required|email|max:64',
            'app_mail_address_from' => 'required|email|max:64',
            'app_mail_name_from' => 'required|min:3|max:64'
        ]);

        if ($v->fails()) {
            return response()->json([
              'status' => 'error',
              'errors' => $v->errors()
            ], 422);
        }

        auth()->user()->app_name = $app_name;
        auth()->user()->app_contact = $app_contact;
        auth()->user()->app_mail_address_from = $app_mail_address_from;
        auth()->user()->app_mail_name_from = $app_mail_name_from;
        auth()->user()->app_host = $app_host;
        auth()->user()->save();

        return response()->json([
            'status' => 'success'
        ], 200);
    }

    /**
     * Validate domain.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function validateDomain($domain) {
        $validDomain = true;

        // Remove http(s)
        if (Str::startsWith($domain, ['http://', 'https://'])) {
            $msg = ['domain' => ['Remove http:// or https://']];
            return response()->json([
              'status' => 'error',
              'errors' => $msg
            ], 422);
        }

        // Domain validation
        $v = \Validator::make(['domain' => 'http://' . $domain], [
            'domain' => 'required|url'
        ]);

        if ($v->fails()) {
            return response()->json([
              'status' => 'error',
              'errors' => $v->errors()
            ], 422);
        }

        // Domain unique validation
        $v = \Validator::make(['domain' => $domain], [
            'domain' => 'unique:users,app_host,' . auth()->user()->id
        ]);

        if ($v->fails()) {
            return response()->json([
              'status' => 'error',
              'errors' => $v->errors()
            ], 422);
        }

        // Sub dir validation
        $parts = parse_url('http://' . $domain);

        if (isset($parts['host']) && strpos($parts['host'], '.') === false) {
            $validDomain = false;
            $msg = ['domain' => ["The domain format is invalid."]];
        }

        if (isset($parts['path']) && $parts['path'] != '') {
            $validDomain = false;
            $msg = ['domain' => ["The domain cannot have a path"]];
        }

        if (! $validDomain) {
            return response()->json([
              'status' => 'error',
              'errors' => $msg
            ], 422);
        }

        return true;
    }
}

