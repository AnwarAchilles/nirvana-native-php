<?php



class Nirvana {

  public static function environment( $env ) {
    NirvanaCore::$Configure = $env['Configure'];

    self::_service();

    NirvanaCore::setMethod( $env );
    NirvanaCore::setResponse( $env );
    NirvanaCore::setService( $env );
  }

  public static function ifNotFound() {
    header('Content-Type: application/json');
    echo json_encode(NirvanaCore::$response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die;
  }

  public static function data() {
    // NirvanaCore::$rest[]
  }

  public static function response( $code ) {
    http_response_code($code);
    NirvanaCore::$response['state'] = $code;
  }

  public static function method($key) {
    if (isset(NirvanaCore::$method[$key])) {
      return NirvanaCore::$method[$key];
    }else {
      return false;
    }
  }

  public static function load( $request, $name ) {
    if (isset(NirvanaCore::$rest[$request][$name])) {
      return NirvanaCore::$rest[$request][$name];
    }
  }

  public static function rest( $request, $name, $controller ) {
    NirvanaCore::$rest[$request][$name] = $controller;

    if (NirvanaCore::$request==$request) {
      if (NirvanaCore::$route == $name) {
        $response = $controller();
        if (is_array($response)) {
          NirvanaCore::$response['data'] = $response;
          header('Content-Type: application/json');
          echo json_encode(NirvanaCore::$response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          die;
        }
      }
    }
  }

  public static function _service() {
    NirvanaCore::$Service['baseurl'] = function() {
      function baseurl() {
        return NirvanaCore::$Configure['baseurl'];
      }
    };
  }

}