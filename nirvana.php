<?php

/**
 * The core class for Nirvana.
 *
 * @class NirvanaCore
 */
class NirvanaCore {

  public static $version = 1.2;
  
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
    'session'=> true
  ];

  public static $service = [];

  

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
    NirvanaCore::defaultService();
    foreach (self::$service as $name => $funct) {
      if (!function_exists($name)) {
        $funct();
      }
    }
  }

  /**
   * Sets the default services.
   *
   * This function sets the default services like baseurl, dd, segment, router, force_https, and anti_ddos.
   *
   * @throws None
   * @return void
   */
  public static function defaultService() {
    NirvanaCore::$service['baseurl'] = function() {
      function baseurl($url='') {
        return NirvanaCore::$configure['baseurl'] . '/' . $url;
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
    NirvanaCore::$service['not_found'] = function() {
      function not_found() {
        header('Content-Type: application/json');
        echo json_encode(NirvanaCore::$response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        die;
      }
    };
    NirvanaCore::$service['is_similar_pattern'] = function() {
      function is_similar_pattern($string1, $string2) {
        // Mengganti {id} dengan pola yang bisa diterima, misalnya \d+ (angka)
        $pattern = preg_quote($string1, '/');  // Menyaring karakter-karakter khusus di dalam string
        $pattern = str_replace('\{id\}', '\d+', $pattern);  // Ganti {id} dengan angka
    
        // Cek apakah string kedua cocok dengan pola yang telah dibuat
        return preg_match("/^$pattern$/", $string2);
      }
    };
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

    self::doSanitizeMethod();

    if ($configure['development']) {
      self::$response['[+] Referrer'] = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
      self::$response['[+] Request'] = self::$request;
      self::$response['[+] Endpoint'] = self::$route;
      self::$response['[+] Method'] = self::$method;
      self::$response['[+] Version'] = self::$version;
    }
    self::$response['state'] = 200;
  }
  
  /**
   * Sets custom error and exception handlers.
   *
   * This function sets a custom error handler that converts PHP errors into
   * ErrorException, and a custom exception handler that formats the exception
   * details into a JSON response and outputs it.
   *
   * @throws ErrorException if a PHP error occurs.
   * @return void
   */
  public static function errorHandler() {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    });
    
    set_exception_handler(function ($exception) {
      NirvanaCore::$response['error'] = [
          'status' => 'error',
          'message' => $exception->getMessage(),
          'file' => $exception->getFile(),
          'line' => $exception->getLine()
      ];
      header('Content-Type: application/json');
      http_response_code(500);
      NirvanaCore::$response['state'] = 500;
      echo json_encode(NirvanaCore::$response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      die;
    });
  }


  /**
   * Extracts the id parameter from the given $pattern and $value.
   *
   * @param string $pattern The pattern to extract the id from.
   * @param string $value The value to extract the id from.
   *
   * @return array|null The extracted id parameter, or null if the pattern and value do not match.
   */
  public static function extractId($pattern, $value) {
    // Menghapus karakter { dan } pada pattern agar bisa dipisahkan
    preg_match_all('/\{(\w+)\}/', $pattern, $matches);

    // Memecah pola menjadi array berdasarkan "/"
    $patternParts = explode('/', $pattern);
    $valueParts = explode('/', $value);

    // Jika jumlah bagian pattern dan value tidak sama, berarti tidak cocok
    if (count($patternParts) !== count($valueParts)) {
        return null;
    }

    // Mengumpulkan nilai parameter dinamis dalam array
    $params = [];
    foreach ($patternParts as $index => $part) {
        // Jika pola berisi {parameter}, maka ambil nilai yang ada pada value
        if (preg_match('/\{(\w+)\}/', $part, $matches)) {
            $params[$matches[1]] = $valueParts[$index];
        }
    }

    return $params;
  }

  /**
   * Sanitizes all the values in the `method` array and stores it back in the same array.
   *
   * This method is used to sanitize all the method values in one go, instead of
   * sanitizing each value individually.
   *
   * @return void
   */
  public static function doSanitizeMethod() {
    foreach (self::$method as $key => $value) {
      self::$method[$key] = self::sanitizeMethod($value);
    }
  }

  /**
   * Sanitizes a given input string to prevent various types of attacks
   *
   * This method is used to sanitize all the method values in one go, instead of
   * sanitizing each value individually. It trims the input string, removes
   * unwanted characters, prevents XSS by converting HTML characters to entities,
   * and removes non-printable ASCII characters. If the input is a number, it
   * converts the input to an integer.
   *
   * @param string $input The input string to sanitize.
   *
   * @return string The sanitized input string.
   */
  public static function sanitizeMethod($input) {
    // Trim: Menghapus spasi ekstra di awal dan akhir string
    $input = trim($input);
    
    // Menghindari karakter-karakter berbahaya dalam nama file (jika digunakan untuk file upload)
    $input = preg_replace('/[^a-zA-Z0-9_\-\.@]/', '', $input);  // Menambahkan @ untuk email

    // Mencegah XSS dengan mengonversi karakter-karakter HTML menjadi entitas HTML
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

    // Menghapus karakter-karakter berbahaya (misal: karakter kontrol atau karakter yang tidak diinginkan)
    $input = preg_replace('/[^\x20-\x7E]/', '', $input); // Menghapus karakter non-printable ASCII

    // Jika input merupakan angka, pastikan hanya angka yang diterima
    // (misalnya jika input adalah ID atau nomor telepon)
    if (is_numeric($input)) {
        $input = (int)$input; // mengonversi input menjadi integer
    }

    return $input;
  }

}

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