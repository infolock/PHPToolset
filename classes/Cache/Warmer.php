<?php
/**
 * @author Jonahton Hibbard
 * Cache Warmer
 */
class Cache_Warmer implements Cache {
  protected $cache;
  protected $forceRefresh;
  protected static $refreshed = array();

  function __construct(Cache $cache, $forceRefresh = false) {
    $this->cache = $cache;
    $this->forceRefresh = $forceRefresh;
  }

  function put($key, $val, $ttl = 0) {
    self::$refreshed[$key] = true;
    $this->cache->put($key, $val, 0);
    $this->cache->put("ttl:$key", $ttl, $ttl);
  }

  function get($key) {
    if ($this->forceRefresh && !isset(self::$refreshed[$key])) {
      self::log("Forcing refresh on cache key $key");
      return null;
    }
    return $this->cache->get($key);
  }

  function rm($key) {
    $this->cache->rm("ttl:$key");
  }

  function isExpired($key) {
    return $this->cache->isExpired("ttl:$key");
  }
}
?>