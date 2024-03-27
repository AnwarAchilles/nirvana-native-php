<?php

/**
 * The core class for Nirvana.
 *
 * @class NirvanaCore
 */
class NirvanaCore {

  public static $version = 1.1;
  
  public static $request = "";
  
  public static $route = "";
  
  public static $method = [];

  public static $header = [];

  public static $response = [];

  public static $data = [];

  public static $rest = [];

  public static $store = [];

  public static $configure = [
    'baseurl'=> 'http://127.0.0.1',
  ];

  public static $service = [
    'baseurl'=> function() {
      function baseurl($url='') {
        return NirvanaCore::$configure['baseurl'] . $url;
      }
    },

    'dd'=> function() {
      function dd($data) {
        echo '<pre>'; print_r($data); die; exit;
      }
    },

    'segment'=> function() {
      function segment($index) {
        $segment = explode('/', NirvanaCore::$route);
        if (isset($segment[$index])) {
          return $segment[$index];
        }else {
          return false;
        }
      }
    },

    'router'=> function() {
      function page($page) {
        if ((preg_replace("/i=[12]/", "", NirvanaCore::$route) == $page) || (segment(0) == $page)) {
          return true;
        }else {
          return false;
        }
      }
    },

    'force_https'=> function() {
      function force_https() {
        if ($_SERVER["HTTPS"] != "on") {
          // Dapatkan URL saat ini
          $url = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
          // Alihkan ke URL HTTPS
          header("Location: $url");
          exit();
        } 
      }
    },

    "anti_ddos"=> function() {
      function anti_ddos($time) {
        // Lakukan pengecekan jika sudah ada data Anti-DDoS
        $currentTime = microtime(true);
        $startTime = $_SESSION['ANTI_DDOS']['time'];
        $timeDiffMs = ($currentTime - $startTime) * 1000; // Konversi ke milidetik
  
        // Jika waktu mikro kurang dari 100ms, tampilkan isi session
        if (($timeDiffMs < $time) && ($_SESSION['ANTI_DDOS']['data'] == $_SERVER['REMOTE_ADDR'])) {
          http_response_code(404);
          echo 'bangke kau main ddos';
          die; exit;
        }
  
        $_SESSION['ANTI_DDOS'] = [
          "time" => microtime(true),
          "data" => $_SERVER['REMOTE_ADDR']
        ];
      }
    }

  ];

  

  /**
   * Checks if a string is a valid JSON.
   *
   * @param string $string The string to be checked.
   * @return bool Returns true if the string is valid JSON, false otherwise.
   */
  public static function _isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
  }

  /**
   * Sets the service.
   *
   * This function loops through the $service array and checks if each function exists.
   * If a function does not exist, it is called.
   *
   * @throws Some_Exception_Class description of exception
   * @return void
   */
  public static function setservice() {
    foreach (self::$service as $name => $funct) {
      if (!function_exists($name)) {
        $funct();
      }
    }
  }

  /**
   * Set the HTTP method, route, and request parameters.
   *
   * @param mixed $configure The configuration options.
   * @throws Some_Exception_Class The exception that can be thrown.
   * @return void
   */
  public static function setMethod( $configure ) {
    if ($_SERVER['REQUEST_METHOD']) {
      self::$request = $_SERVER['REQUEST_METHOD'];
      $ROUTE = "";
      
      if (isset($_SERVER['QUERY_STRING'])) {
        $ROUTE = urldecode($_SERVER['QUERY_STRING']);
      }else if (isset($_SERVER['REQUEST_URI'])) {
        $ROUTE = urldecode($_SERVER['REQUEST_URI']);
      }
      
      $parse_url_2 = parse_url($ROUTE);
      if (isset($parse_url_2['path'])) {
        self::$route = ltrim($parse_url_2['path'], '/');
      }
      if (self::_isJson(file_get_contents('php://input'))) {
        self::$method = json_decode(file_get_contents('php://input'), true);
      }else {
        parse_str(file_get_contents('php://input'), self::$method);
      }
      
      $QUERY = ltrim(strchr(urldecode($_SERVER['REQUEST_URI']), '?'), '?');
      if (str_contains($QUERY, '?')) {
        parse_str($QUERY, self::$method);
      }
      
      if (count($_POST)!==0) {
        self::$method = $_POST;
      }

      if (isset($parse_url_2['query'])) {
        parse_str($parse_url_2['query'], NirvanaCore::$method);
      }
    }
  }

  /**
   * Set the response for the given environment.
   *
   * @param array $env The environment array.
   */
  public static function setResponse( $env ) {
    $configure = $env['configure'];

    if ($configure['development']) {
      self::$response['[+] Baseurl'] = $configure['baseurl'];
      self::$response['[+] Request'] = self::$request;
      self::$response['[+] Endpoint'] = self::$route;
      self::$response['[+] Method'] = self::$method;
      self::$response['[+] Version'] = self::$version;
    }
    self::$response['state'] = 200;
  }

}