<?php 

namespace Platform\Controllers\Staff;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Staff;
use App\Http\Controllers;
use Platform\Controllers\Core;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SegmentController extends Controller {

  /*
   |--------------------------------------------------------------------------
   | Segment related functions
   |--------------------------------------------------------------------------
   */

  /**
   * Get campaign segments
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getSegments() {
      $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereUuid(request('campaign', 0))->firstOrFail();
      $segments = $campaign->business->segments->pluck('name', 'id');

      return response()->json($segments, 200);
  }
}