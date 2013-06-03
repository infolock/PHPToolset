<?php
/**
 * @author Jonahton Hibbard
 * EAccelerator Cache Wrapper
 */
class Cache_EAccelerator implements Cache {
  function put($key, $val, $ttl = 0) {
    eaccelerator_put($key, $val, $ttl);
  }

  function get($key) {
    return eaccelerator_get($key);
  }

  function rm($key) {
    return eaccelerator_rm($key);
  }

  function isExpired($key) {
    return eaccelerator_get($key) === null;
  }
}
?>