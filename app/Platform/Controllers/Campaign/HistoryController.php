<?php 

namespace Platform\Controllers\Campaign;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Customer;
use App\Http\Controllers;
use Platform\Controllers\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller {

    /*
     |--------------------------------------------------------------------------
     | History related functions
     |--------------------------------------------------------------------------
     */

    /**
     * Get customer history.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getHistory(Request $request) {
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('uuid', 0))->firstOrFail();
      $user = Customer::withoutGlobalScopes()->where('id', Auth::user('customer')->id)->where('campaign_id', $campaign->id)->firstOrFail();
      $locale = request('locale', config('system.default_language'));

      return response()->json($user->getHistory($locale));
    }
}