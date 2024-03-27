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

/**
 * The Handler class for NirvanaStore.
 *
 * @class NirvanaStore
 */
class NirvanaStore {

  private $source = '';
  
  private $data = [];

  private $loop = 0;

  private $state = [
    'FREE'=> FALSE,
    'FILE'=> FALSE,
    'DATA'=> FALSE,
  ];

  /**
   * Constructor for the class.
   *
   * @param datatype $name description
   * @param datatype $data description
   * @throws Some_Exception_Class description of exception
   * @return Some_Return_Value
   */
  public function __construct( $name, $data=[] ) {
    $this->source = NirvanaCore::$configure['basedir'].'/'.$name.'.store.json';
    
    if (!isset(NirvanaCore::$store[$name])) {
      $this->state['FREE'] = TRUE;  
    }

    if ($this->state['FREE']) {
      if (!file_exists($this->source)) {
        if (touch($this->source)) {
          $this->state['FILE'] = TRUE;
        }
      }else {
        $this->state['FILE'] = TRUE;
      }
    }

    if ($this->state['FILE']) {
      if (empty(file_get_contents($this->source))) {
        $parse = [];
        foreach ($data as $id=>$row) {
          $parse[] = array_merge(['id'=>$id+1], $row);
        }
        if (file_put_contents($this->source, json_encode(['time'=>time(), 'loop'=>count($parse)+1, 'data'=>$parse], JSON_PRETTY_PRINT))) {
          $this->state['DATA'] = TRUE;
        }
      }else {
        $this->state['DATA'] = TRUE;
      }
    }
    
    if ($this->state['DATA']) {
      $this->loop = json_decode(file_get_contents($this->source), true) ['loop'];
      $this->data = json_decode(file_get_contents($this->source), true) ['data'];
    }
  }

  /**
   * Saves the data to a file.
   *
   * @return bool
   */
  private function save() {
    $data = [];
    $data['time'] = time();
    $data['loop'] = $this->loop;
    
    $this->data = array_values($this->data);
    $data['data'] = $this->data;
    
    if (file_put_contents($this->source, json_encode($data, JSON_PRETTY_PRINT))) {
      return true;
    }
  }


  /**
   * find function searches the data array for items based on the specified field and value.
   *
   * @param datatype $field description
   * @param datatype $value description
   * @return array
   */
  public function find($field, $value = null) {
    $result = array();
    foreach ($this->data as $item) {
      if ($value === null && isset($item[$field])) {
        $result[] = $item;
      } elseif ($value !== null && isset($item[$field]) && $item[$field] == $value) {
        $result[] = $item;
      }
    }
    return $result;
  }

  /**
   * Set the data and increment the loop counter.
   *
   * @param mixed $data The data to be set
   */
  public function set( $data ) {
    $this->data[] = array_merge(['id'=>$this->loop], $data);
    $this->loop = $this->loop + 1;
    $this->save();
  }


  /**
   * Get data based on the provided ID.
   *
   * @param mixed $id The ID to retrieve data for. Defaults to an empty string.
   * @return mixed The data associated with the provided ID, or the entire data if no ID is provided.
   */
  public function get( $id='' ) {
    if (empty($id)) {
      return $this->data;
    }else {
      if (is_array($id)) {
        $packet = [];
        foreach ($id as $row) {
          if (isset($this->data[$row])) {
            $packet[] = $this->data[$row];
          }
        }
        return $packet;
      }else {
        if (self::find('id', $id)) {
          return self::find('id', $id);
        }
      }
    }
  }


  /**
   * Update a resource in the data array.
   *
   * @param mixed $id The identifier of the resource to update
   * @param array $data The data to merge with the resource
   */
  public function put( $id, $data ) {
    if (is_array($id)) {
      foreach ($id as $row) {
        if (isset($this->data[$row])) {
          $this->data[$row] = array_merge($this->data[$row], $data);
          $this->save();
        }
      }
    }else {
      foreach ($this->data as $key=>$row) {
        if ($id == $row['id']) {
          $row = array_merge($row, $data);
          $this->data[$key] = $row;
          $this->save();
        }
      }
    }
  }


  /**
   * Delete the specified item(s) from the data based on the given ID(s).
   *
   * @param mixed $id The ID or array of IDs to be deleted
   */
  public function del( $id ) {
    if (is_array($id)) {
      foreach ($id as $row) {
        $targetID = array_search($row, array_column($this->data, 'id'));
        if ($targetID) {
          unset($this->data[$targetID]);
          $this->save();
        }
      }
    }else {
      $targetID = array_search($id, array_column($this->data, 'id'));
      if ($targetID) {
        unset($this->data[$targetID]);
        $this->save();
      }
    }
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
    NirvanaCore::$configure = ($env['configure']) ? $env['configure'] : [];
    NirvanaCore::$service = ($env['service']) ? $env['service'] : [];

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
  }

}