<?php
/**
 * @author Jonathon Hibbard
 * Class for creating an EAccelerator Instance Object.
 */
class Cache_EAccelerator_Instance implements Cache {
  public function put($key, $val, $ttl = 0) {
    eaccelerator_put($key, $val, $ttl);
  }

  public function get($key) {
    return eaccelerator_get($key);
  }

  public function getModulePaths() {
    $val = $this->get('module_paths');
    if(empty($val)) return false;
    return unserialize($val);
  }

  public function putModulePaths($val) {
    $this->put('module_paths', serialize($val), 0);
  }

  public function rm($key) {
    return eaccelerator_rm($key);
  }

  public function isExpired($key) {
    return eaccelerator_get($key) === null;
  }
}
?>