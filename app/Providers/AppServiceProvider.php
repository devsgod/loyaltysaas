<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\User;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Fix for "Specified key was too long error" error
        // https://laravel-news.com/laravel-5-4-key-too-long-error
        \Schema::defaultStringLength(191);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $account = null;

        if (! $this->app->runningInConsole() && \Platform\Controllers\InstallationController::isInstalled() && \Request::segment(1) != 'install' && \Schema::hasTable('users')) {
          $hostname = $this->app['request']->getHost();

          if (auth('api')->check()) {
            $account = auth('api')->user()->account;
          } else {
            $account = User::withoutGlobalScopes()->where('app_host', $hostname)->first();
          }

          // If url is not resolved, check campaign
          if ($account === null) {
            $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->where('active', 1)->where('host', $hostname)->first();
            if ($campaign !== null) {
              $account = $campaign->account;
              $account->campaign_id = $campaign->id;
            }
          }

          //$account = (app()->bound('account')) ? app()->make('account') : null;
          if ($account !== null) {
            $account->found = true;
            if (! isset($account->campaign_id)) $account->campaign_id = null;

            $this->app->instance('account', $account);

            // Override config
            if ($account->app_name !== null) config(['app.name' => $account->app_name]);
            config(['app.url' => request()->getSchemeAndHttpHost()]);
            //if ($account->app_host !== null) config(['app.url' => $account->app_host]);
            if ($account->app_mail_name_from !== null) config(['general.mail_name_from' => $account->app_mail_name_from]);
            if ($account->app_mail_address_from !== null) config(['general.mail_address_from' => $account->app_mail_address_from]);

            if ($account->app_color === null) $account->app_color = '#304FFE';
            if ($account->app_name === null) $account->app_name = config('app.name');
            if ($account->app_host === null) $account->app_host = request()->getHost();
            $account->app_scheme = request()->getScheme();
            $account->version = config('versions.script_version');

            config(['general.cname_domain' => $account->app_host]);

            $account->config = [
              'pusher' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'app_id' => config('broadcasting.connections.pusher.app_id'),
                'options' => config('broadcasting.connections.pusher.options')
              ]
            ];

            // Variables for views
            view()->composer('*', function ($view) use ($account) {
              // view()->share('key', 'value');
            });
          }
        }

        if ($account === null) {
            $account = new \stdClass;
            $account->found = false;
            $account->campaign_id = null;
            $account->app_vendor_id = null;
            $account->id = 1;
            $account->role = 2;
            $account->app_color = '#304FFE';
            $account->app_name = config('app.name');
            $account->app_host = request()->getHost();
            $account->app_scheme = request()->getScheme();
            $account->version = config('versions.script_version');
            $account->config = [];

            $this->app->instance('account', $account);
        }
    }
}
