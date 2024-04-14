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
    NirvanaCore::$configure = (isset($env['configure'])) ? $env['configure'] : [];
    NirvanaCore::$service = (isset($env['service'])) ? $env['service'] : [];

    self::_service();

    NirvanaCore::setMethod( $env );
    NirvanaCore::setResponse( $env );
    NirvanaCore::setService( $env );
  }

  /**
   * Sends a JSON response with the content of NirvanaCore::$response
   * and stops the execution of the script.
   *
   * @throws None
   * @return None
   */
  public static function ifNotFound() {
    header('Content-Type: application/json');
    echo json_encode(NirvanaCore::$response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die;
  }

  /**
   * Retrieves the data.
   *
   * @throws Some_Exception_Class description of exception
   * @return Some_Return_Value
   */
  public static function data( $source ) {
    // return new NirvanaData( NirvanaCore::$configure['basedir'].'/'.$source.'.store.json' );
  }

  /**
   * Sets the HTTP response code and updates the state of the response.
   *
   * @param int $code The HTTP response code to set.
   */
  public static function response( $code ) {
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
  public static function method($key) {
    if (isset(NirvanaCore::$method[$key])) {
      return NirvanaCore::$method[$key];
    }else {
      return false;
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

  public static function store( $name, $data='' ) {
    if (empty($data)) {
      return NirvanaCore::$store[$name];
    }else {
      // NirvanaCore::$Store[$name] = $data;
      NirvanaCore::$store[$name] = new NirvanaStore($name, $data);
    }
  }


  /**
   * Set the base URL for the service.
   *
   * This function sets the base URL for the service by assigning a closure to the 'baseurl' key in the NirvanaCore::$service array. The closure returns the value of the 'baseurl' key in the NirvanaCore::$configure array.
   *
   * @throws None
   * @return None
   */
  public static function _service() {
    NirvanaCore::$service['baseurl'] = function() {
      function baseurl($url='') {
        return NirvanaCore::$configure['baseurl'] . $url;
      }
    };
    NirvanaCore::$service['dd'] = function() {
      function dd($data) {
        echo '<pre>'; print_r($data); die; exit;
      }
    };
    NirvanaCore::$service['segment'] = function() {
      function segment($index) {
        $segment = explode('/', NirvanaCore::$route);
        if (isset($segment[$index])) {
          return $segment[$index];
        }else {
          return false;
        }
      }
    };
    NirvanaCore::$service['router'] = function() {
      function router($page) {
        if ((preg_replace("/i=[12]/", "", NirvanaCore::$route) == $page) || (segment(0) == $page)) {
          return true;
        }else {
          return false;
        }
      }
    };
    NirvanaCore::$service['force_https'] = function() {
      function force_https() {
        if ($_SERVER["HTTPS"] != "on") {
          // Dapatkan URL saat ini
          $url = "https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
          // Alihkan ke URL HTTPS
          header("Location: $url");
          exit();
        } 
      }
    };
    NirvanaCore::$service['anti_ddos'] = function() {
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
    };
  }

}