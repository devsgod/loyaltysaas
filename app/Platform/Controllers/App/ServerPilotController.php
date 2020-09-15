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
use Illuminate\Support\Arr;

class ServerPilotController extends \App\Http\Controllers\Controller
{
  /*
   |--------------------------------------------------------------------------
   | ServerPilot Controller
   |--------------------------------------------------------------------------
   */

  /**
   * Add domain
   * \Platform\Controllers\App\ServerPilotController::addDomain($host, $ssl_app_id);
   *
   * @return api response or false
   */
  public static function addDomain($host, $ssl_app_id) {
    if (config('general.serverpilot_client_id') !== null && config('general.serverpilot_key') !== null && $ssl_app_id !== null && $host !== null && $host != '') {
      $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.serverpilot.io/v1/']);
      $response = $client->request('GET', 'apps/' . $ssl_app_id, ['auth' => [config('general.serverpilot_client_id'), config('general.serverpilot_key')]]);
      $response = json_decode($response->getBody());
      $domains = $response->data->domains;

      if (! in_array($host, $domains)) {
        $domains[] = $host;
        $response = $client->request('POST', 'apps/' . $ssl_app_id, ['auth' => [config('general.serverpilot_client_id'), config('general.serverpilot_key')], \GuzzleHttp\RequestOptions::JSON => ['domains' => $domains]]);
        return $response;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * Delete domain
   * \Platform\Controllers\App\ServerPilotController::deleteDomain($host, $ssl_app_id);
   *
   * @return api response or false
   */
  public static function deleteDomain($host, $ssl_app_id) {
    if (config('general.serverpilot_client_id') !== null && config('general.serverpilot_key') !== null && $ssl_app_id !== null && $host !== null && $host != '') {
      // Delete domain from ServerPilot
      $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.serverpilot.io/v1/']);
      $response = $client->request('GET', 'apps/' . $ssl_app_id, ['auth' => [config('general.serverpilot_client_id'), config('general.serverpilot_key')]]);
      $response = json_decode($response->getBody());
      $domains = $response->data->domains;

      if (in_array($host, $domains)) {
        $key = array_search($host, $domains);
        unset($domains[$key]);
        $response = $client->request('POST', 'apps/' . $ssl_app_id, ['auth' => [config('general.serverpilot_client_id'), config('general.serverpilot_key')], \GuzzleHttp\RequestOptions::JSON => ['domains' => $domains]]);
        return $response;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
}