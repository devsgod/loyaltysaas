<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
| https://laravel.com/docs/5.8/controllers#resource-controllers
|
*/

// Public routes
Route::get('i18n/campaign_translations', '\Platform\Controllers\Core\Content@getAvailableCampaignTranslations');
Route::get('i18n/campaign/{lang}', '\Platform\Controllers\Core\Content@getCampaignTranslations');

Route::group(['prefix' => 'localization'], function() {
    Route::get('locales', '\Platform\Controllers\Core\Localization@getLocales');
    Route::get('timezones', '\Platform\Controllers\Core\Localization@getTimezones');
    Route::get('currencies', '\Platform\Controllers\Core\Localization@getCurrencies');
});

Route::group(['prefix' => 'get'], function() {
    Route::get('terms', '\Platform\Controllers\Campaign\CampaignController@getTerms');
    Route::get('site/terms', '\Platform\Controllers\Website\SiteController@getTerms');
});

// Payment webhooks
Route::post('webhooks/paddle', '\Platform\Controllers\App\PaddleController@postWebhook');
Route::post('webhooks/2checkout/ipn', '\Platform\Controllers\App\TwoCheckoutController@postIpn');
Route::post('webhooks/stripe', '\Platform\Controllers\App\StripeController@postWebhook');

// Secured app routes
Route::group(['middleware' => 'auth:api'], function() {

    // Admin related routes
    Route::group(['prefix' => 'admin',  'middleware' => 'role:1'], function () {
        Route::get('stats', '\Platform\Controllers\App\AdminController@getStats');
        Route::post('verify-domain', '\Platform\Controllers\App\AdminController@postVerifyDomain');
        Route::post('verify-dns', '\Platform\Controllers\App\AdminController@postVerifyDns');
        Route::post('business', '\Platform\Controllers\App\AdminController@postSaveBusiness');
        Route::post('payment', '\Platform\Controllers\App\AdminController@postSavePayment');
        Route::get('branding', '\Platform\Controllers\App\AdminController@getBranding');
        Route::post('branding', '\Platform\Controllers\App\AdminController@postUpdateBranding');
    });

    // User related routes
    Route::group(['prefix' => 'user',  'middleware' => 'role:3'], function () {
        Route::get('stats', '\Platform\Controllers\App\UserController@getStats');
        Route::post('analytics/earning', '\Platform\Controllers\App\AnalyticsController@getUserEarningAnalytics');
        Route::post('analytics/spending', '\Platform\Controllers\App\AnalyticsController@getUserSpendingAnalytics');

        // User plans
        Route::get('plans', '\Platform\Controllers\App\UserController@getPlans');

        // Stripe
        Route::post('stripe/token', '\Platform\Controllers\App\StripeController@postToken');
        Route::post('stripe/cancel', '\Platform\Controllers\App\StripeController@postCancelSubscription');
    });
});

// App authorization routes
Route::prefix('auth')->group(function () {
    Route::post('register', '\Platform\Controllers\App\AuthController@register');
    Route::post('login', '\Platform\Controllers\App\AuthController@login');
    Route::post('impersonate', '\Platform\Controllers\App\AuthController@impersonate');
    Route::get('refresh', '\Platform\Controllers\App\AuthController@refresh');
    Route::post('password/reset', '\Platform\Controllers\App\AuthController@passwordReset');
    Route::post('password/reset/validate-token', '\Platform\Controllers\App\AuthController@passwordResetValidateToken');
    Route::post('password/update', '\Platform\Controllers\App\AuthController@passwordUpdate');

    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('user', '\Platform\Controllers\App\AuthController@user'); // Get user details
        Route::post('logout', '\Platform\Controllers\App\AuthController@logout');
        Route::post('profile', '\Platform\Controllers\App\AuthController@postUpdateProfile');
    });
});

// Secured app routes
Route::group(['prefix' => 'app', 'middleware' => 'auth:api'], function() {

    // DataTable
    Route::get('data-table', '\Platform\Controllers\App\DataTableController@getDataList');
    Route::post('data-table/delete', '\Platform\Controllers\App\DataTableController@postDeleteRecords');
    Route::get('data-table/export', '\Platform\Controllers\App\DataTableController@getExport');

    // DataForm
    Route::get('data-form', '\Platform\Controllers\App\DataFormController@getDataForm');
    Route::post('data-form/relation', '\Platform\Controllers\App\DataFormController@postGetRelation');
    Route::post('data-form/save', '\Platform\Controllers\App\DataFormController@postSaveRecord');
});

// Campaigns
Route::prefix('campaign/auth')->group(function () {
    Route::post('register', '\Platform\Controllers\Campaign\AuthController@register');
    Route::post('login', '\Platform\Controllers\Campaign\AuthController@login');
    Route::get('refresh', '\Platform\Controllers\Campaign\AuthController@refresh');
    Route::post('password/reset', '\Platform\Controllers\Campaign\AuthController@passwordReset');
    Route::post('password/reset/validate-token', '\Platform\Controllers\Campaign\AuthController@passwordResetValidateToken');
    Route::post('password/update', '\Platform\Controllers\Campaign\AuthController@passwordUpdate');

    Route::group(['middleware' => 'auth:customer'], function() {
        Route::get('user', '\Platform\Controllers\Campaign\AuthController@user'); // Get user details
        Route::post('logout', '\Platform\Controllers\Campaign\AuthController@logout');
        Route::post('profile', '\Platform\Controllers\Campaign\AuthController@postUpdateProfile');
    });
});

// Secured customer routes
Route::group(['prefix' => 'campaign', 'middleware' => 'auth:customer'], function() {

    // Get points total for customer
    Route::get('points', '\Platform\Controllers\Campaign\PointController@getCustomerPoints');

    // Get history for customer
    Route::get('history', '\Platform\Controllers\Campaign\HistoryController@getHistory');

    // -------------------------------------------------------------------------------------------------------------------------------------------------------
    // Routes related to earning points
    // -------------------------------------------------------------------------------------------------------------------------------------------------------
 
    // Generate token for use with QR / links
    Route::post('get-claim-points-token', '\Platform\Controllers\Campaign\PointController@postGetClaimToken');

    // Customer verifies code generated by merchant
    Route::post('earn/verify-customer-code', '\Platform\Controllers\Campaign\PointController@postVerifyCustomerCode');

    // Routes for merchant interactions on customer's device
    Route::post('earn/verify-merchant-code', '\Platform\Controllers\Campaign\PointController@postVerifyMerchantCode');
    Route::post('earn/process-merchant-entry', '\Platform\Controllers\Campaign\PointController@postProcessMerchantEntry');

    // -------------------------------------------------------------------------------------------------------------------------------------------------------
    // Routes related to redeeming rewards
    // -------------------------------------------------------------------------------------------------------------------------------------------------------

    // Generate token for use with QR / links
    Route::post('get-redeem-reward-token', '\Platform\Controllers\Campaign\RewardController@postGetRedeemRewardToken');

    // Routes for merchant interactions on customer's device
    Route::post('reward/verify-merchant-code', '\Platform\Controllers\Campaign\RewardController@postVerifyMerchantCode');
    Route::post('reward/process-merchant-entry', '\Platform\Controllers\Campaign\RewardController@postProcessMerchantEntry');
});

// Staff
Route::prefix('staff/auth')->group(function () {
    Route::post('login', '\Platform\Controllers\Staff\AuthController@login');
    Route::get('refresh', '\Platform\Controllers\Staff\AuthController@refresh');
    Route::post('password/reset', '\Platform\Controllers\Staff\AuthController@passwordReset');
    Route::post('password/reset/validate-token', '\Platform\Controllers\Staff\AuthController@passwordResetValidateToken');
    Route::post('password/update', '\Platform\Controllers\Staff\AuthController@passwordUpdate');

    Route::group(['middleware' => 'auth:staff'], function() {
        Route::get('user', '\Platform\Controllers\Staff\AuthController@user'); // Get user details
        Route::post('logout', '\Platform\Controllers\Staff\AuthController@logout');
        Route::post('profile', '\Platform\Controllers\Staff\AuthController@postUpdateProfile');
    });
});

// Secured staff routes
Route::group(['prefix' => 'staff', 'middleware' => 'auth:staff'], function() {

    // Get history for staff member
    Route::get('history', '\Platform\Controllers\Staff\HistoryController@getHistory');

    // Get campaign segments
    Route::get('segments', '\Platform\Controllers\Staff\SegmentController@getSegments');

    // Get campaign rewards
    Route::get('rewards', '\Platform\Controllers\Staff\RewardController@getRewards');

    // -------------------------------------------------------------------------------------------------------------------------------------------------------
    // Routes related to earning points
    // -------------------------------------------------------------------------------------------------------------------------------------------------------

    // Routes for customer links
    Route::post('points/validate-link-token', '\Platform\Controllers\Staff\PointController@postValidateLinkToken');
    Route::post('points/push/credit', '\Platform\Controllers\Staff\PointController@postPushCreditsByToken');
    
    // Routes for customer codes
    Route::get('points/customer/active-codes', '\Platform\Controllers\Staff\PointController@getActiveCustomerCodes');
    Route::post('points/customer/generate-code', '\Platform\Controllers\Staff\PointController@postGenerateCustomerCode');

    // Routes for merchant code
    Route::get('points/merchant/active-code', '\Platform\Controllers\Staff\PointController@getActiveMerchantCode');
    Route::post('points/merchant/generate-code', '\Platform\Controllers\Staff\PointController@postGenerateMerchantCode');

    // Route for customer number
    Route::post('points/customer/credit', '\Platform\Controllers\Staff\PointController@postCreditCustomer');

    // -------------------------------------------------------------------------------------------------------------------------------------------------------
    // Routes related to redeeming rewards
    // -------------------------------------------------------------------------------------------------------------------------------------------------------

    // Routes for customer links
    Route::post('rewards/validate-link-token', '\Platform\Controllers\Staff\RewardController@postValidateLinkToken');
    Route::post('rewards/push/redemption', '\Platform\Controllers\Staff\RewardController@postRedeemRewardByToken');

    // Routes for merchant code
    Route::get('rewards/merchant/active-code', '\Platform\Controllers\Staff\RewardController@getActiveMerchantCode');
    Route::post('rewards/merchant/generate-code', '\Platform\Controllers\Staff\RewardController@postGenerateMerchantCode');

    // Route for customer number
    Route::post('rewards/customer/credit', '\Platform\Controllers\Staff\RewardController@postRedeemReward');
});