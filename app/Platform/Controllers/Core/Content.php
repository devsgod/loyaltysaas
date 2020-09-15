<?php 

namespace Platform\Controllers\Core;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class Content extends \App\Http\Controllers\Controller {

  /*
   |--------------------------------------------------------------------------
   | Content Controller
   |--------------------------------------------------------------------------
   |
   | Gets and parses translation files
   |--------------------------------------------------------------------------
   */

  /**
   * Get all available translations for loyalty campaigns
   *
   * @return \Symfony\Component\HttpFoundation\Response 
   */
  public function getAvailableCampaignTranslations() {
    $dir_path = resource_path() . '/lang';
    $dir = new \DirectoryIterator($dir_path);
    $response = [];
    foreach ($dir as $fileinfo) {
      if (! $fileinfo->isDot()) {
        // Check if campaign translations exist
        $lang = $fileinfo->getFilename();
        if (strlen($lang) == '2' && \File::exists($dir_path . '/' . $lang . '/campaign.php')) {
          $file = include($dir_path . '/' . $lang . '/campaign.php');
          $response[] = [
            'title' => $file['language_title'],
            'abbr' => $file['language_abbr'],
            'code' => $lang
          ];
        }
      }
    }

    return response()->json($response, 200);
  }

  /**
   * Get translations for loyalty campaigns
   *
   * @return \Symfony\Component\HttpFoundation\Response 
   */
  public function getCampaignTranslations($lang) {
    // Set locale
    $locale = request('lang', config('system.default_language'));
    app()->setLocale($locale);

    // Get language file content
    $response = trans('campaign');

    return response()->json($response, 200);
  }
}