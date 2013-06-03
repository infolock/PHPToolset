<?php
/**
 * @author Jonahton Hibbard
 * Null Cache Wrapper
 */
class Cache_Null implements Cache {
  function put($key, $val, $ttl = 0) {
  }

  function get($key) {
    return null;
  }

  function rm($key) {
  }

  function isExpired($key) {
    return null;
  }
}
?>