<?php
/**
 * @author Jonathon Hibbard
 * Redis Operations Wrapper
 */
class Cache_Redis_Operations {
  private static $hosts = null;

  protected $redis    = null;
  const PORT = 6379;
  private $return_code = false;
  protected $miss_key;

  /**
   * Connect to a cache. It is assumed that the connection credentials will be supplied
   * by the class constructor.
   *
   * @access Protected
   *
   * @return Boolean - False if error, True if succss
   */
  protected function connect() {
    if(!isset($this->redis) || !($this->redis instanceof Redis)) {
      try {
        // Start by loading all the valid REDIS hosts.
        if(null == self::$hosts) {
          $this->loadHosts();
        }

        // Now, set the initial return code to false, and
        // make a temporary copy of the host list.
        $this->return_code = false;
        $host_list = self::$hosts;

        // While we still have hosts to try,
        while(count($host_list) > 0) {
          // Try connecting to a random host. (remove the entry from the temp list, so we don't try again)
          $random = mt_rand(0, sizeof($host_list)-1);
          $host_entry = array_splice($host_list, $random, 1);
          if(false != $this->tryHost($host_entry[0])) {
            // If we succeed, break out, and set the return code to success.
            $this->return_code = true;
            break;
          }
        }

        // Now, if we get through the whole list, and we haven't connected... throw an Exception.
        if(false == $this->return_code) {
          error_log("FAILED to connect to Redis on ANY host.");
          throw new Exception("Could not connect to Redis!");
        }

      } catch(Exception $e) {
        throw new Exception("Could not connect to Redis!  " . $e->getMessage());
      }

    }
    return $this->return_code;
  }

  protected function tryHost($host) {
    $return_code = false;

    try {
      $this->redis = new Redis();
      $return_code = $this->redis->connect($host, self::PORT);
      if(false === $return_code) {
        error_log("FAILED to connect to Redis! Trying next host.");
      }
    } catch(Exception $e) {
      error_log("FAILED to connect to Redis! Trying next host.");
      $return_code = false;
    }

    return $return_code;
  }

  protected function loadHosts() {
    if(null == self::$hosts) {
      throw new \Exception('Invalid hosts defined');
    }
    self::$hosts = $hosts;
  }

  /**
   * Promote a Key/Value to the top of a queue specified by key.
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
  protected function promoteKey($key, $value, $age = -1) {
    if(!isset($key) || !is_string($key) || empty($key)) {
      throw new Exception('key must be a non-empty string');
    }
    if(!isset($value) || !is_string($value)) {
      throw new Exception('Value must be a string');
    }

    $return_code =  $this->redis->lPush($key, $value);

    if(isset($age) && $age > 0 && false === $this->redis->setTimeout($key, $age)) {
      error_log('RedisCache::push() - Timeout not set for LIST key = "'. $key .'"');
    }

    return (false !== $return_code);
  }

  /**
   * Retrieve a list value from the cache with the given key.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Protected
   *
   * @param String $key - The key that points to the list.
   * @param int $start - the starting index to retrieve (starts at 0)
   * @param int $end - the ending index to retrieve from (negative are allowed: -1 = end, -2 = next to last, etc.)
   *
   * @return Array or Boolean - The List value associated with the key if it exists, or False if the key does not exist.
   */
  protected function listGetRange($key, $start, $end) {
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
    if($this->redis->exists($key)) {
      $retval = $this->redis->lGetRange($key, $start, $end);
    }

    if(!$retval) {
      $this->increment($this->miss_key);
    }

    return $retval;
  }

  /**
   * returns the number of elements in a specified list.
   *
   * @param String $key - The name of the list to retrieve the size for.
   *
   * @return Int or Boolean - The number of elements in the list, or False if the key does not exist, or is not a list.
   */
  protected function listLen($key) {
    if(!isset($key) || !is_string($key) || empty($key)) {
      throw new Exception('key must be a non-empty string');
    }

    return $this->redis->lsize($key);
  }


  /**
   * Get multiple values from cache, querying with wildcards.
   * Because of the frequency that this method is used, no internal logging or tracing will be done at this level.
   *
   * @access Protected
   *
   * @param String $key - a key value, with wildcards. May match multiple entries.
   *
   * @return Array of String - The cache entries that match the key.
   */
  protected function getMulti($key) {
    if(!isset($key) || !is_string($key)) {
      throw new Exception('key must be a string');
    }

    $returned_keys = $this->redis->getKeys($key);
    $returned_values = false;

    if(is_array($returned_keys) && !empty($returned_keys)) {
      $returned_values = $this->redis->getMultiple($returned_keys);
    }

    if(!$returned_values) {
      $this->increment($this->miss_key);
    }

    return $returned_values;
  }

  /**
   * Attempt to atomically set a key in the cahe, if an only if the key does not already exist.
   * Used to obtaining locks. Locks timeout after 10 minutes by default. Clear locks using delete($key).
   *
   * @access Protected
   * @param String $key - The name of the lock to obtain.
   *
   * @return Boolean - True if the lock was obtained, false if not.
   */
  protected function getLock($key) {
    if(!isset($key) || !is_string($key)) {
      throw new Exception('$key must be a string');
    }

    $return_value = $this->redis->setnx($key, (string)getmypid());
    if(true === $return_value) {
      $this->redis->expire($key, 180);
    }

    return $return_value;
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
  protected function push($key, $value, $age = -1) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a non-empty string');
    }

    if(!isset($value) || !is_string($value)) {
      throw new Exception('value must be a string');
    }

    $return_code =  $this->redis->lPush($key, $value);

    if(isset($age) && $age > 0) {
      if(false === $this->redis->setTimeout($key, $age)) {
        error_log('RedisCache::push() - Timeout not set for LIST key = "'. $key .'"');
      }
    }

    return (false !== $return_code);
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
  protected function pop($key) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a non-empty string');
    }
    $return_value = $this->redis->rPop($key);;
    return $return_value;
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
  protected function listTrim($key, $count){
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a string');
    }

    if(!isset($count) || !is_numeric($count) || $count < 0) {
      throw new Exception('count must be a non-negative number');
    }

    return $this->redis->listTrim($key, 0, $count);
  }


  /**
   * Disconnect to a cache.
   *
   * @access Protected
   *
   * @return Boolean - False if error, True if succss
   */
  protected function disconnect() {
    if($this->redis->close() === false) {
      throw new Exception("An unknown error occurred while closing the Redis Connection...");
    }
    return true;
  }

  /**
   * Wrapper class for Redis::info()
   * @see Cache::info()
   * @return array - info dump of Redis cache
   */
  protected function info() {
    $cache_info = $this->redis->info();
    $cache_info['miss_count'] = $this->get($this->miss_key);

    return $cache_info;
  }


  /**
   * wrapper class for Redis::getKeys
   * @param string - patter to search for
   * @return array - array of keys that match the $pattern
   */
  protected function getKeys($pattern) {
    if(!isset($pattern) || !is_string($pattern) || 0 == strlen($pattern)) {
      throw new Exception('pattern must be a non-empty string');
    }

    return $this->redis->getKeys($pattern);
  }


  /**
   * wrapper class for Redis::ttl
   * @param string - patter to search for
   * @return array - array of keys that match the $pattern
   */
  protected function getTTL($key) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a string');
    }

    $retval = $this->redis->ttl($key);
    if(!$retval) {
      $this->increment($this->miss_key);
    }
    return $retval;
  }

  /**
   * wrapper class for Redis lindex
   */
  public function lIndex($key, $index) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a string');
    }

    if(!isset($index) || !is_numeric($index) || $index < 0) {
      throw new Exception('index must be a non-negative number');
    }

    return $this->redis->lindex($key,$index);
  }

  /**
  * wrapper class for Redis lrem
  */
  public function lRem($key, $value, $index = 0) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a string');
    }

    if(!isset($value) || !is_string($value) || strlen($value) == 0) {
      throw new Exception('value must be a string');
    }

    if(!isset($index) || !is_numeric($index)) {
      throw new Exception('index must be a non-negative number');
    }

    return $this->redis->lrem($key,$value, $index);
  }

  /**
  * wrapper class for Redis lrem
  */
  public function lSet($key, $index, $value) {
    if(!isset($key) || !is_string($key) || strlen($key) == 0) {
      throw new Exception('key must be a string');
    }

    if(!isset($value)) {
      throw new Exception('value must not be empty');
    }

    if(!isset($index) || !is_numeric($index)) {
      throw new Exception('index must be a non-negative number');
    }

    return $this->redis->lset($key, $index, $value);
  }


  /**
   *
   */
  public function __toString() {
    return "RedisCache (Host: $this->host Port: $this->port)";
  }
}
?>