<?php
/*
 |--------------------------------------------------------------------------
 | Installation
 |--------------------------------------------------------------------------
 */

Route::get('install', '\Platform\Controllers\InstallationController@getInstall')->name('installation');
Route::post('install', '\Platform\Controllers\InstallationController@postInstall');

/*
|--------------------------------------------------------------------------
| App routes
|--------------------------------------------------------------------------
*/

$account = app()->make('account');
var_dump($account);
if (!$account->found) {
	// The host can not be resolved to a website
	Route::get('/{any?}', '\Platform\Controllers\App\AppController@getAccountNotFound')->where('any', '.*');
} else {
	// App routes (for owners, admins and users)
	Route::get('go/', '\Platform\Controllers\App\AppController@index');

  // Campaign custom domain
  if ($account->campaign_id !== null) {
    // Staff site
    Route::get('staff/', '\Platform\Controllers\Staff\StaffController@getStaffManagement');

    // Campaign routes
    Route::get('/{any?}', '\Platform\Controllers\Campaign\CampaignController@getCampaign')->where('any', '^(?!api\/)[\/\w\.-]*');
  } else {
    // Staff routes for test campaign url
    Route::get('campaign/{any?}/staff/', '\Platform\Controllers\Staff\StaffController@getTestStaffManagement')->where('any', '^(?!api\/)[\/\w\.-]*');

    // Campaign test url
    Route::get('campaign/{any?}', '\Platform\Controllers\Campaign\CampaignController@getTestCampaign')->where('any', '^(?!api\/)[\/\w\.-]*');

    // Website homepage
    Route::get('/{any?}', '\Platform\Controllers\Website\SiteController@home')->where('any', '.*');
  }
}