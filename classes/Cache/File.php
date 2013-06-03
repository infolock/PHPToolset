<?php
/**
 * @author Jonahton Hibbard
 * File Cache Wrapper
 */
class Cache_File implements Cache {
  protected $dir;
  protected $prefix;
  protected $ttl = array();

  public function __construct($dir, $prefix = '') {
    if (!file_exists($dir)) {
      mkdir($dir, 0777, true);
    }

    $this->dir = $dir;
    $this->prefix = $prefix;

    $this->ttlFile = "$dir/{$prefix}ttl.php";
    if (file_exists($this->ttlFile)) {
      include $this->ttlFile;
    }
  }

  public function getFilename($key) {
    return "{$this->dir}/{$this->prefix}$key";
  }

  protected function atomicWrite($file, $content) {
    $tmp = $file . getmypid();
    file_put_contents($tmp, $content);
    @rename($tmp, $file);
  }

  protected function updateInternals() {
    $this->atomicWrite($this->ttlFile,
    '<?php $this->ttl = ' .
    var_export($this->ttl, true) .
    ';');
  }

  public function put($key, $val, $ttl = 0) {
    $this->ttl[$key] = $ttl;
    $this->atomicWrite($this->getFilename($key), $val);
    $this->updateInternals();
  }

  public function get($key) {
    if ($this->isExpired($key)) {
      $this->rm($key);
      return null;
    }

    $file = $this->getFilename($key);
    if (!file_exists($file)) {
      return null;
    }
    return file_get_contents($file);
  }

  public function rm($key) {
    if (!isset($this->ttl[$key])) {
      return null;
    }
    unset($this->ttl[$key]);
    @unlink($this->getFilename($key));
    $this->updateInternals();
    return true;
  }

  public function isExpired($key) {
    if (file_exists($this->ttlFile)) {
      include $this->ttlFile;
    }
    if (!isset($this->ttl[$key])) {
      return true;
    }
    if ($this->ttl[$key] === 0) {
      return false;
    }
    @clearstatcache();
    $t = filemtime($this->getFilename($key));
    return $t + $this->ttl[$key] <= time();
  }
}
?>