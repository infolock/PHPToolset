<?php
/**
 * @author Jonathon Hibbard
 * The main Cache Factory for creating a Cache Object.
 *
 * @example $redis = new Cache_Factory("redis");
 * @example $eAccellerator = new Cache_Factory("eAccellerator");
 */
class Cache_Factory {
  private static $eaccelleratorObj = null;
  private static $redisObj = null;

  /**
   * Gets an Instance for any available Cache Systems we support...
   *
   * @param  string $cache_type // Can be (currently): redis, eAccellerator
   * @return Cache object
   */
  public static function getInstance($cache_type = NULL) {
    if(!isset($cache_type) || !is_string($cache_type) || empty($cache_type)) {
      throw new Exception("Invalid Cache Type Requested!");
    }

    switch(strtolower($cache_type)) {
      case 'eaccelerator':
        if(null === self::$eaccelleratorObj) {
          self::$eaccelleratorObj = new Cache_EAccelerator_Instance();
        }
        return self::$eaccelleratorObj;

      case 'redis':
//        error_log("Getting Redis Instance....");
        if(null === self::$redisObj) {
//          error_log("Creating Redis Instance...");
          self::$redisObj = new Cache_Redis_Instance();
        }
//        error_log("Returning Redis Instance...");
//        error_log("Cache == " . var_export(self::$redisObj, true));
        return self::$redisObj;

      default:
        throw new Exception("Invalid Cache Type Requested!");
    }
  }
}
?>