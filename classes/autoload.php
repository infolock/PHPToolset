<?php
/**
 * @author Jonathon Hibbard
 * A Singleton autoloader class for managing the autoloading of classes using namespaces and pathing.
 *
 * It is recommended that this file be loaded in the php.ini file as an autoloaded source.
 * Otherwise, this file will need to be included within each file that wishes to take advantage of
 * its autoloading behavior.
 *
 * In order to take full advantage of this application,you must properly namespace your classes.  Rule of thumb is, whatever you name your class (case and all)
 * should be exactly what the filename of your class is as well.  Your Namespace Does not have to be the same case and structure as your directory, but it is
 * recommended to do so as this will help readability.
 *
 * ====== NOTE =======
 * This autoloader class is self-initing.  The last line of code in this file will issue: MyProject\autoloader::getInstance(); to start it up...
 *
 * @example
 * include_once('autoloader.php');
 * autoloader::getInstance();  // This is just to show how it is loaded as it has no effect since this is called at the bottom of this file.
 *
 * @example
* include_once('autoloader.php');
 * autoloader::getInstance()->register("MyClass", "/path/to/MyClass");  // In this instance, calling getInstanace actually matters!!
 */

namespace MyProject;

class autoloader {
  const NAMESPACE_SEPARATOR     = '\\';
  const PRIMARY_PROJECT_NAMESPACE = 'MyProject'; // Realize that MyProject is just an example here.  Just want to use a default parent namespace.

  private static $common_autoloader_loaded = false;
  private static $instance = NULL;
  /**
   * Used to iterate over namespaces and their locations.
   * @var type
   */
  private $namespaces = array();

  private static function start_common_autoload() {
    spl_autoload_register(array(self::PRIMARY_PROJECT_NAMESPACE . '\autoloader', 'common_autoload'));
  }

  /**
   * Loads the common_autoloader on the first call.  This allows all existing code to work who don't use namespaces.
   * This also registers the project's namespace with the current folder being the initial directory...
   * @return type object autoloader
   */
  public static function getInstance() {
    if(false === self::$common_autoloader_loaded) {
      self::start_common_autoload();
      self::$common_autoloader_loaded = true;
    }
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Defines autoload strategy for common classes
   */
  public static function common_autoload($class) {
    $classfile = __DIR__ . '/' . strtr($class, '_', '/').'.php';
    if (file_exists($classfile)) {
      self::loadFile($classfile);
    }
  }

  /**
   * @access private
   * Registers the current directory to be the root path of the PRIMARY_PROJECT_NAMESPACE.
   * @see PRIMARY_PROJECT_NAMESPACE
   */
  private function __construct() {
    $this->register(self::PRIMARY_PROJECT_NAMESPACE, __DIR__);
  }

  /**
   * Defines the location of a namespace
   * @param string $namespace
   * @param string $dir
   */
  public function registerNamespace($namespace, $dir) {
    $this->namespaces[$namespace] = $dir;
  }

  /**
   * This is the namespace-based autoloader.
   * @param string $class  // Name of the class to load.
   * @return boolean  // Returns true if the file load was successful, false if not.
   */
  public function autoload($class) {
    if($this->loadClass($class)) {
      return $class;
    }
    return false;
  }

  /**
   * Parses out the Namespace from the file, gets the path from the namespaces array, and then attempts to loadit.
   * @param string $class  // Class to be loaded.
   * @return boolean // Returns true if succeeds, false if not.
   */
  public function loadClass($class) {
    $namespace = strstr($class, '\\', true);

    if(!empty($namespace) && false !== $namespace && isset($this->namespaces[$namespace])) {
      $classPeeled = preg_replace("/$namespace/", "", $class, 1);
      $filename = $this->classnameToFilename($classPeeled, $this->namespaces[$namespace]);
    } else {
      $filename = $class . ".php";
    }

    return __NAMESPACE__ . autoloader::loadFile($filename);
  }

  /**
   * Registers a namespace and also loads the namespace directory into the autoload register.
   * @param type $namespace
   * @param type $dir
   */
  public function register($namespace, $dir) {
    $this->registerNamespace($namespace, $dir);
    spl_autoload_register(array($this, 'autoload'));
  }

  /**
   * Takes in a class name and a directory and returns it as a full path.
   * @param string $class // Name of the class we want to load.
   * @param string $dir // Directory to append to the class
   * @return string // Path to the class
   */
  public function classnameToFilename($class, $dir) {
    return $dir . str_replace(self::NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $class) . '.php';
  }

  /**
   * Handles loads of files.
   * Optional settings allows us to maintains the active paths included in php's include_path.
   *
   * @param string $filename  // The filename we want to load
   * @param boolean $once // If set to false, this will cause the loader to include the file rather than include_once.
   */
  public static function loadFile($filename, $once = true) {
    if($once === true) {
      include_once($filename);
    } else {
      include($filename);
    }
  }
}

autoloader::getInstance();

/**
 * @example
 * Example with the DBHandler
 */
//\MyProject\Database\DBHandler::getInstance();
?>