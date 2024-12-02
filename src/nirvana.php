<?php


/**
 * The Main class for Nirvana.
 *
 * @class Nirvana
 */
class Nirvana {

  /**
   * Sets the environment for the function.
   *
   * @param array $env The environment configuration.
   * @throws Exception If there is an error in the configuration.
   * @return void
   */
  public static function environment( $env ) {
    NirvanaCore::$data = array_merge(NirvanaCore::$data, (isset($env['data'])) ? $env['data'] : []);
    NirvanaCore::$configure = array_merge(NirvanaCore::$configure, (isset($env['configure'])) ? $env['configure'] : []);
    NirvanaCore::$service = array_merge(NirvanaCore::$service, (isset($env['service'])) ? $env['service'] : []);

    // set session
    if (NirvanaCore::$configure['session']) {
      session_start();
    }

    NirvanaCore::setMethod( $env );
    NirvanaCore::setResponse( $env );
    NirvanaCore::setService( $env );

    // set aliases instance
    if (isset(NirvanaCore::$configure['alias'])) {
      class_alias('Nirvana', NirvanaCore::$configure['alias']);
    }
  }


  /**
   * Prints the documentation for the Nirvana API.
   *
   * The documentation displays a list of all the available endpoints, along
   * with their respective URLs.
   *
   * @return void
   */
  public static function documentation() {
    echo '<h1 style="margin-bottom:0">Nirvana Documentation</h1>';
    echo '<p style="margin-top:0">Version - '.NirvanaCore::$version.'</p>';
    echo '<hr>';
    
    foreach (NirvanaCore::$rest as $key => $value) {
      echo '<fieldset>';
      echo '<legend>'.$key.'</legend>';
      echo '<ol type="1">';
      foreach ($value as $k => $v) {
        echo "<li>".baseurl($k)."</li>";
      }
      echo "</ol>";
      echo '</fieldset>';
    }

    die;
  }

  /**
   * Retrieves the data.
   *
   * @throws Some_Exception_Class description of exception
   * @return Some_Return_Value
   */
  public static function data( $name = null, $data = null ) {
    if (empty($data)) {
      if (empty($name)) {
        return (object) NirvanaCore::$data;
      }else {
        return (isset(NirvanaCore::$data[$name])) ? NirvanaCore::$data[$name] : null;
      }
    }else {
      NirvanaCore::$data[$name] = $data;
    }
  }

  /**
   * Sets the HTTP state code and updates the state of the state.
   *
   * @param int $code The HTTP state code to set.
   */
  public static function state( $code ) {
    http_response_code($code);
    NirvanaCore::$response['state'] = $code;
  }

  /**
   * A description of the entire PHP function.
   *
   * @param datatype $key description
   * @throws Some_Exception_Class description of exception
   * @return Some_Return_Value
   */
  public static function method($key, $value = false) {
    if (isset(NirvanaCore::$method[$key])) {
      if (empty(NirvanaCore::$method[$key])) {
        NirvanaCore::$method[$key] = $value;
      }
      return NirvanaCore::sanitizeMethod(NirvanaCore::$method[$key]);
    }else {
      NirvanaCore::$method[$key] = $value;
      return NirvanaCore::sanitizeMethod($value);
    }
  }

  /**
   * Loads a specific value from the NirvanaCore REST array.
   *
   * @param datatype $request description of the request parameter
   * @param datatype $name description of the name parameter
   * @return mixed the value loaded from the NirvanaCore REST array
   */
  public static function load( $request, $name ) {
    if (isset(NirvanaCore::$rest[$request][$name])) {
      return NirvanaCore::$rest[$request][$name];
    }
  }

  /**
   * Registers a REST endpoint.
   *
   * @param mixed $request The type of request (GET, POST, etc.).
   * @param string $name The name of the endpoint.
   * @param callable $controller The function to handle the endpoint.
   * @return void
   */
  public static function rest( $request, $name, $controller ) {
    NirvanaCore::$rest[$request][$name] = $controller;
    if (NirvanaCore::$request==$request) {
      if (!empty(NirvanaCore::$route)) {
        if (is_similar_pattern($name, NirvanaCore::$route)) {
          NirvanaCore::errorHandler();
          header('Content-Type: application/json');
          $params = NirvanaCore::extractId($name, NirvanaCore::$route);
          $response = call_user_func_array($controller, $params);
          if (is_array($response)) {
            NirvanaCore::$response['data'] = $response;
            echo json_encode(NirvanaCore::$response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            die;
          }
        }
      }
    }
  }

  /**
   * Manages session-based storage for a given store name.
   *
   * Initializes a session store if it does not exist and provides methods
   * to set, check, retrieve, delete, and clear data within the session store.
   *
   * @param string $storeName The name of the session store to manage.
   * @return string The class name of the dynamically created store.
   */
  public static function store( $storeName ) {
    $store = new class {
      public static $name = "";
      public static function init( $name ) {
        self::$name = $name;
        if (!isset($_SESSION[self::$name])) {
          $_SESSION[self::$name] = [];
        }
      }
      public static function set($name, $data) {
        $_SESSION[self::$name][$name] = $data;
      }
      public static function has($name) {
        return isset($_SESSION[self::$name][$name]);
      }
      public static function get($name=null) {
        if (empty($name)) {
          return $_SESSION[self::$name];
        }else {
          return $_SESSION[self::$name][$name];
        }
      }
      public static function delete($name) {
        unset($_SESSION[self::$name][$name]);
      }
      public static function clear() {
        unset($_SESSION[self::$name]);
        return true;
      }
    };
    $store::init($storeName);

    return $store::class;

    // old version
    // if (empty($data)) {
    //   return NirvanaCore::$store[$name];
    // }else {
    //   NirvanaCore::$store[$name] = new NirvanaStore($name, $data);
    // }
  }

}