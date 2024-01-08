<?php


class NirvanaCore {

  public static $version = 1.1;
  
  public static $request = "";
  
  public static $route = "";
  
  public static $method = [];

  public static $header = [];

  public static $response = [];

  public static $data = [];

  public static $rest = [];
  
  public static $Configure = [
    'baseurl'=> 'http://127.0.0.1',
  ];

  public static $Service = [];

  

  public static function _isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
  }

  public static function setService() {
    foreach (self::$Service as $name => $funct) {
      if (!function_exists($name)) {
        $funct();
      }
    }
  }

  public static function setMethod( $Configure ) {
    if ($_SERVER['REQUEST_METHOD']) {
      self::$request = $_SERVER['REQUEST_METHOD'];
      if (isset($_SERVER['QUERY_STRING'])) {
        $ROUTE = urldecode($_SERVER['QUERY_STRING']);

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
  }

  public static function setResponse( $env ) {
    $Configure = $env['Configure'];

    if ($Configure['development']) {
      self::$response['[+] Baseurl'] = $Configure['baseurl'];
      self::$response['[+] Request'] = self::$request;
      self::$response['[+] Endpoint'] = self::$route;
      self::$response['[+] Method'] = self::$method;
      self::$response['[+] Version'] = self::$version;
    }
    self::$response['state'] = 200;
  }

}