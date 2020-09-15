<?php namespace Platform\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Core;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

class InstallationController extends \App\Http\Controllers\Controller {

	/*
	|--------------------------------------------------------------------------
	| Installation Controller
	|--------------------------------------------------------------------------
	|
	*/

  public function __construct() {
    if ($this->isInstalled() && \Request::segment(2) != 'update') {
      abort(404);
    }
  }

	/**
	 * Check for installation
	 */
	public static function isInstalled() {
    return (\File::exists(base_path('.env'))) ? true : false;
	}

	/**
	 * Installation view
	 */
	public function getInstall() {
    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

    foreach ($timezones as $timezone) {
        $tzList[$timezone] = str_replace('_', ' ', $timezone);
    }

		return view('installation.install', compact('tzList'));
	}

	/**
	 * Post installation
	 */
  public function postInstall(Request $request) {
		# https://stackoverflow.com/a/28898174
		if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
		if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
		if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

    set_time_limit(500);

    $name = $request->input('name', '');
    $email = $request->input('email', '');
    $pass = $request->input('pass', '');

    $APP_KEY = 'base64:'.base64_encode(
      Encrypter::generateKey(config('app.cipher'))
    );

    // Get .env.example file as blueprint
    $env = \File::get(base_path('.env.example'));

    $all = $request->except(['email', 'pass']);
    //$all['APP_KEY'] = $APP_KEY;
    $all['APP_DEBUG'] = 'false';
    $all['APP_ENV'] = 'production';

    // Loop through .env.example and set config
    $new_env = '';

    foreach(preg_split("/((\r?\n)|(\r\n?))/", $env) as $line) {
      $cfg_found = false;

      foreach ($all as $key => $value) {
        if (Str::startsWith($line, $key . '=')) {
          $cfg_found = true;
          if ($value == 'true' || $value == 'false' || is_numeric($value)) {
            $new_env .= $key . '=' . $value . '' . PHP_EOL;
          } else { 
            $new_env .= $key . '="' . $value . '"' . PHP_EOL;
          }
        }
      }

      if (! $cfg_found) {
        $new_env .= $line . PHP_EOL;
      }
    }

    \File::put(base_path('.env'), $new_env);

    \Artisan::call('install');

    $user = \App\User::find(1);
    $user->name = $name;
    $user->email = $email;
    $user->password = bcrypt($pass);
    $user->save();

    return redirect(url('/'));
	}
}