<?php
/**
 * @author Jonahton Hibbard
 * Static Cache Wrapper
 */
class Cache_Static implements Cache {
  protected $cache;

  function __construct(Cache $cache) {
    $this->cache = $cache;
  }

  function put($key, $val, $ttl = 0) {
  // NOTE expects cache to be populated
  // TODO throw an exception?
  }

  function get($key) {
    return $this->cache->get($key);
  }

  function rm($key) {
    // NOTE never removes keys
  }

  function isExpired($key) {
    return false;
  }
}
?>