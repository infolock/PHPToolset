<?php
/**
 * @author Jonathon Hibbard
 * Class for creating a Redis Instance Object.
 *
 * @uses Cache_Redis_Operations | Cache
 * @final
 */
final class Cache_Redis_Instance extends Cache_Redis_Operations implements Cache {
  /**
   * Create an instance of RedisCache and prepare to connect to an instance of the
   * cache engine specified by the passed in host and port.
   *
   * @access Public
   * @return Boolean - False if error, True if succss
   */
  public function __construct() {
    if(false === $this->connect()) {
      throw new Exception("Failed to connect to Redis!");
    }
  }

  /**
  * Push a value to the top of a queue specified by key.
  * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
  *
  * @access Protected
  *
  * @param String $key - the name of the queue to push the value to.
  * @param String $value - the value to place in the queue
  * @param int $age - If set, and a positive integer, the key will timeout after this many seconds.
  *
  * @return Boolean - True if success, False on failure.
  */
  public function push($key, $value, $age = -1) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a non-empty string');
    }

    if(!isset($value) || !is_string($value)) {
      throw new Exception('value must be a string');
    }

    $return_code =  $this->redis->lPush($key, $value);

    if(isset($age) && $age > 0) {
      if( false === $this->redis->setTimeout($key, $age)) {
        error_log('RedisCache::push() - Timeout not set for LIST key = "'. $key .'"');
      }
    }

    return (false !== $return_code);
  }

  /**
   * Wrapper method for Redis EXPIRE.
   *
   * Allows us to update the expiration of a key
   *
   * @param string  $key        - The key we want to update the expiration time for.
   * @param integer $expiration - The time to set an expiration to be for the key (DEFAULT is 180 (3 minutes));
   *
   * @return boolean            - Returns TRUE on success, FALSE on error
   */
  public function expire($key, $time = 180) {
    if(!is_string($key)) {
      throw new Exception('Key recieved by ' . __FUNCTION__ . ' must be a string!  FAILED in ' . __CLASS__ . '!');
    }

    $time = intval($time);
    if($time < 1) {
      throw new Exception('TIME recieved by ' . __FUNCTION__ . ' must be an integer representing seconds greater than 0!  FAILED in ' . __CLASS__ . '!');
    }
    $this->redis->expire($key, $time);
  }

  /**
   * Pop a value off the top of the queue and return it. Returns false if no entries exist in the queue.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Protected
   *
   * @param String $key - The name of the queue to pop from.
   *
   * @return String - the next value in the queue, or False if no value is in the queue.
   */
  public function pop($key) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a non-empty string');
    }
    $return_value = $this->redis->rPop( $key );;
    return $return_value;
  }

  /**
   * Set a value for a specific key in the cache, with a given time to live (in seconds).
   * After the time to live has expired, the key/value pair will be deleted from the cache.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Public
   *
   * @param String $key
   * @param String $value
   * @param Integer $age (optional) - The time to live in seconds (-1 means do not expire. Defaults to 12 hours).
   *
   * @return Boolean - True on success, False on failure.
   */
  public function put($key, $value, $age = 43200) {
    if(!isset($key) || !is_string($key)) {
      throw new Exception('$key must be a string');
    }
    if(!isset( $value ) || !is_string($value)) {
      throw new Exception('$value must be a string');
    }
    if(!isset($age)) {
      throw new Exception('$age must be a positive integer');
    }

    $age = intval($age);
    if($age < 0) {
      return $this->redis->set($key, $value);
    } else {
      return $this->redis->setex($key, $age, $value);
    }
  }

  public function putModulePaths($val) {
    $this->put('module_paths', json_encode($val), -1);
  }

  /**
  * Trims the given list to contain $count number of items (keeps the newest).
  *
  * @access Protected
  *
  * @param String $key - The name of the list to trim.
  * @param Int $count - The number of items in the list to keep.
  *
  * @return Boolean - True on success, False on error.
  */
  public function listTrim($key, $count){
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a string');
    }

    if( !isset($count) || !is_numeric($count) || $count < 0) {
      throw new Exception('count must be a non-negative number');
    }

    return $this->redis->listTrim($key, 0, $count);
  }

  /**
   * Retrieve a value from the cache with the given key.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Public
   *
   * @param String $key
   *
   * @return String or Boolean - The string value associated with the key if it exists, or False if the key does not exist.
   */
  public function get($key) {
    if(!isset($key) || !is_string($key) || empty($key)) {
      throw new Exception('key must be a string');
    }

    return $this->redis->get($key);
  }

  public function getModulePaths() {
    $val = $this->get('module_paths');
    if(empty($val)) return false;
    return json_decode($val, true);
  }

  /**
   * Delete a value from the cache with the given key.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Public
   *
   * @param String or Array of String $key - One or more keys to delete from the cache.
   *
   * @return Integer - The number of keys deleted from the cache.
   */
  public function rm($key) {
    if(!isset($key) || !is_string($key) || empty($key)) {
      throw new Exception('key must be a string');
    }

    return $this->redis->delete($key);
  }

  /**
   * Checks to see if a key exists.
   *
   * @param  string $key
   * @return boolean
   */
  public function isExpired($key) {
    if(!isset($key) || !is_string($key) || empty($key)) {
      throw new Exception('key must be a string');
    }
    return $this->redis->exists($key);
  }

  /**
   * Retrieve a list value from the cache with the given key.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Protected
   *
   * @param String $key - The key that points to the list.
   * @param int $start - the starting index to retrieve (starts at 0)
   * @param int $end - the ending index to retrieve from (negative are allowed: -1 = end, -2 = next to last, etc. )
   *
   * @return Array or Boolean - The List value associated with the key if it exists, or False if the key does not exist.
   */
  public function listGetRange( $key, $start = 0, $end = -1) {
    if(!isset($key) || !is_string($key) || empty($key)) {
      throw new Exception('key must be a string');
    }

    $start = intval($start);
    if($start < 0) {
      throw new Exception('start must be a nonnegative integer.');
    }

    if(!isset($end) || !is_numeric($end)) {
      throw new Exception('end must be an integer.');
    }

    $retval = false;
    $retval = $this->redis->lGetRange($key, $start, $end);

    return $retval;
  }

  /**
   * returns the number of elements in a specified list.
   *
   * @param String $key - The name of the list to retrieve the size for.
   *
   * @return Int or Boolean - The number of elements in the list, or False if the key does not exist, or is not a list.
   */
  public function listLen($key) {
    if(!isset($key) || !is_string( $key) || empty($key)) {
      throw new Exception('key must be a non-empty string');
    }

    return $this->redis->lsize($key);
  }
}
?>