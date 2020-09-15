<?php namespace Platform\Controllers\Core;

class Secure extends \App\Http\Controllers\Controller {

  /**
   * Generate random string based on charset
   * $code = \App\Core\Secure::getRandom(12);
   *
   * @return string
   */
  public static function getRandom($length, $charset = '1234567890') {
    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count-1)];
    }
    return $str;
  }

  /**
   * Array to encrypted string - $sl = \App\Core\Secure::array2string(array('id' => 1))
   */
  public static function array2string($array)
  {
    $string = http_build_query($array);
    $string =  \Crypt::encrypt($string);
    return rawurlencode($string);
  }

  /**
   * Encrypted string to array - $sl = \App\Core\Secure::string2array($sl)
   */
  public static function string2array($string)
  {
    try {
      $string = rawurldecode($string);
      $string = \Crypt::decrypt($string);
    }
    catch(\Illuminate\Encryption\DecryptException  $e)
    {
      echo 'Decrypt Error';
      die();
    }

    parse_str($string, $array);
    return $array;
  }

  /**
   * Short hash ONLY for numbers, for example user_id to create upload directory. $hash = \App\Core\Secure::staticHash(1)
   */
  public static function staticHash($number, $obfuscate = false)
  {
    $hashids = new \Hashids\Hashids(\Config::get('app.key'));

    if ($obfuscate) {
      $number = $number * intval(config()->get('system.obfuscator_prefix'));
    }

    $string = $hashids->encode($number);
    return $string;
  }

  /**
   * Decode hash. $number = \App\Core\Secure::staticHashDecode($hash)
   */
  public static function staticHashDecode($hash, $obfuscate = false)
  {
    $hashids = new \Hashids\Hashids(\Config::get('app.key'));
    $number = $hashids->decode($hash);
    if (isset($number[0])) {
      $number = $number[0];

      if ($obfuscate) {
        $number = intval($number) / intval(config()->get('system.obfuscator_prefix'));
      }
    } else {
      $number = false;
    }

    return $number;
  }

  /**
   * Obfuscate email address with JavaScript
   * \App\Core\Secure::hideEmail($email)
   */
  public static function hideEmail($email) {
    $obfuscated = "";
    for ($i=0; $i<strlen($email); $i++){
      $obfuscated .= "&#" . ord($email[$i]) . ";";
    }
    return $obfuscated;
  }
}
