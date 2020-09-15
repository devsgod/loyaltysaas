<?php 

namespace Platform\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Customer;
use App\Http\Controllers;
use Carbon\Carbon;
use Platform\Controllers\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller {

    /*
     |--------------------------------------------------------------------------
     | Single Page Application Controller for campaign pages
     |--------------------------------------------------------------------------
     |
     | Campaign logic
     |--------------------------------------------------------------------------
     */

    /**
     * Initialize the campaign on test url
     *
     * @return \Illuminate\View\View
     */
    public function getTestCampaign($slug = null) {
      $account = app()->make('account');
      //$account = (object) $account->only('app_name', 'app_headline', 'app_scheme', 'app_host', 'language', 'locale');

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
        return redirect($campaign->url, 301);
      }

      $website = $campaign->getCampaignWebsite();
      $config = $account->config;

      return view('campaign.index', compact('account', 'website', 'config'));
    }

    /**
     * Initialize the campaign
     *
     * @return \Illuminate\View\View
     */
    public function getCampaign() {
      $account = app()->make('account');

      $hostname = request()->getHost();
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->where('host', $hostname)->first();

      // 404 if campaign not found
      if (empty($campaign)) {
        return view('campaign.campaign-404', compact('account'));
      }

      $website = $campaign->getCampaignWebsite();
      $config = $account->config;

      return view('campaign.index', compact('account', 'website', 'config'));
    }

    /**
     * This page is shown when no campaign is found
     *
     * @return \Illuminate\View\View
     */
    public function getCampaignNotFound() {
      $account = app()->make('account');
      return view('campaign-404', compact('account'));
    }

    /**
     * Get terms
     *
     * @return \Illuminate\View\View
     */
    public function getTerms() {
      $account = app()->make('account');
      $company = request()->get('company', $account->app_name);

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $view = (\File::exists(resource_path() . '/views/campaign/terms-' . $locale . '.blade.php')) ? 'campaign.terms-' . $locale : 'campaign.terms';

      return view($view, compact('account', 'company'));
    }

}