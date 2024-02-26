<?php


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
    $this->source = NirvanaCore::$Configure['basedir'].'/'.$name.'.store.json';
    
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
        if (isset($this->data[$id])) {
          return $this->data[$id];
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
      if (isset($this->data[$id])) {
        $this->data[$id] = array_merge($this->data[$id], $data);
        $this->save();
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