<?php 

namespace Platform\Controllers\Staff;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Staff;
use App\Http\Controllers;
use Platform\Controllers\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller {

  /*
   |--------------------------------------------------------------------------
   | Single Page Application Controller for Staff Area
   |--------------------------------------------------------------------------
   |
   | Staff area logic
   |--------------------------------------------------------------------------
   */

  /**
   * Get staff management for test campaign
   *
   * @return \Illuminate\View\View
   */
  public function getTestStaffManagement($slug = null) {
      $account = app()->make('account');

      if ($slug == null) {
        return redirect('//' . $account->app_host);
      }

      $slug = explode('/', $slug);
      $slug = $slug[0] ?? null;
      if ($slug == '') $slug = null;

      // Get campaign, if exists
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->where('account_id', $account->id)->where('slug', $slug)->first();

      // 404 if campaign not found
      if (empty($campaign)) {
        return view('campaign.campaign-404', compact('account'));
      }

      // If host exists, redirect to domain if test url is opened
      if ($campaign->host !== null) {
        return redirect($campaign->url . '/staff', 301);
      }

      $website = $campaign->getCampaignWebsite();

      return view('staff.index', compact('account', 'website'));
  }

  /**
   * Get staff management
   *
   * @return \Illuminate\View\View
   */
  public function getStaffManagement() {
      $account = app()->make('account');

      $hostname = request()->getHost();
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->where('host', $hostname)->first();

      // 404 if campaign not found
      if (empty($campaign)) {
        return view('campaign.campaign-404', compact('account'));
      }

      $website = $campaign->getCampaignWebsite();

      return view('staff.index', compact('account', 'website'));
  }
}