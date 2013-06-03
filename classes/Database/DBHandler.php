<?php
/**
 * @author Jonathon Hibbard
 *
 * dbHandler is a simple Singleton Object for managing database connections to MySQL.
 * @throws PDOException, Exception
 *
 * @example #1
 *  // This example uses the default database info connection information
 *
 *    try {
 *      $dbHandle = \dbHandler::getInstance()->dbHandle;
 *    } catch(PDOException $e) {
 *      // handle the exception...
 *    }
 *
 *    $sql = "SELECT * FROM `my_table`";
 *    try {
 *      $rst = $dbHandle->fetchAll($sql);
 *    } catch(\PDOExeption $e) {
 *      // handle the exception...
 *    }
 *
 * @example #2
 *  // This example uses the default database info connection information
 *    $sql = "SELECT * FROM `my_table`";
 *    try {
 *      $rst = \dbHandler::getInstance()->dbHandle->fetchAll($sql);
 *    } catch(\PDOExeption $e) {
 *      // handle the exception...
 *    }
 */
namespace MyProject\Database;
class DBHandler {

  const DB_HOST = '';  // Optional - Default Hostname here
  const DB_NAME = '';  // Optional - Default Database Name to connect to here
  const USERNAME = ''; // Optional - Default Username here
  const PASSWORD = ''; // Optional - Default Password here

  private static $db_info_keys = array("hostname","dbname","username","password");
  private static $db_info = array();
  public static $instance = null;
  public static $instances = array();

  public $dbhandle;

  /**
   * Main constructor
   *
   * @access private
   * @param array $db_info
   * @throws \PDOException
   */
  private function __construct(array $db_info = null) {
    if(!isset($db_info) || empty($db_info)) {
      $db_info = array('hostname' => self::DB_HOST,
                       'dbname'   => self::DB_NAME,
                       'username' => self::USERNAME,
                       'password' => self::PASSWORD,
                      );
    } else {

      self::$db_info = $db_info;

      if(false === self::isValidDbInfo($db_info)) {
        throw new \PDOException("Invalid DB_Info Passed!");
      }
    }

    self::connectWithDbInfo($db_info);
  }

  /**
   * Gateway to connecting
   *
   * @param array $db_info
   * @throws \PDOException
   */
  private function connectWithDbInfo(array $db_info) {

    try {
      $this->dbHandle = new \PDO('mysql:host=' . $db_info['hostname'] . ';dbname=' . $db_info['dbname'], $db_info['username'], $db_info['password']);
      $this->dbHandle->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->dbHandle->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
      $this->dbHandle->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

    } catch(\PDOException $e) {
      // Catch and clear here to generate a more safe report without exposing connection information.
      $this->dbHandle = null;
      throw new \PDOException("ERROR connect to database!!  Error Thrown was: " . $e->getMessage());
    }
  }


  /**
   * Helper method to ensure all the key found within the db_info array is a valid key for connections.
   *
   * @param array $db_info
   * @return Boolean TRUE if all required values were found, FALSE otherwise.
   */
  private function isValidDbInfoKey($key) {
    if(!in_array($key, self::$db_info_keys) || !preg_match("/^[A-Za-z0-9\-_.]+$", $key)) {
      return false;
    }

    if(empty(self::$db_info[$key]) || !is_string(self::$db_info[$key])) {
      return false;
    }

    return true;
  }

  /**
   * Helper method to ensure all the proper keys for connecting to the server were given to the constructor.
   *
   * @param array $db_info
   * @return Boolean TRUE if all required values were found, FALSE otherwise.
   */
  private function isValidDbInfo(array $db_info) {
    $valid_key_count = count(self::$db_info_keys);

    foreach($db_info as $key_name => $db_info_value) {
      if(false == self::isValidDbInfoKey($key_name)) {
        unset($db_info[$key]);
        continue;
      }

      self::$db_info[$key] = $db_info_value;
      $valid_key_count--;
    }

    return ($valid_key_count > 0) ? false : true;
  }

  /**
   * Main entry point for obtaining an instance to the Default DB Singleton instance
   *
   * @return DBHandler instance
   *
   * @see Main Description at the top of this file.
   */
  public static function getInstance() {
    if(!isset(self::$instance)) {
      self::$instance = new dbHandler();
    }
    return self::$instance;
  }

  /**
   * Main entry point for obtaining a instance to a specific Database with info
   *
   * @param array $db_info
   * @return DBHandler instance
   *
   * @example
   * // This example assumes the object is being used to provide singleton
   * // instances to multiple Database connetions, each with their own db_info.
   *
   *    $sql = "SELECT * FROM `my_table`";
   *    $db_info = array("my_db" => array("hostname" => "localhost",
   *                                      "dbname"   => "my_db",
   *                                      "username" => "my_dbUser",
   *                                      "password" => "4lly0urb4s34r3B3l()nGt()us_-ph33r"
   *                                     )
   *                    );
   *    try {
   *      $rst = \dbHandler::getInstanceUsingDbInfo()->dbHandle->fetchAll($sql);
   *    } catch(\PDOExeption $e) {
   *      // handle the exception...
   *    }
   */
  public static function getInstanceUsingDbInfo(array $db_info) {

    if(isset($db_info['dbname']) && !empty($db_info['dbname'])) {
      if(!isset(self::$instances[$db_info['dbname']])) {
        self::$instances[$db_info['dbname']] = new dbHandler($db_info);
      }
      return self::$instances[$db_info['dbname']];
    }
  }

  /**
   * Used to close the instance connection and free up the object.
   *
   * @param string $db_name
   */
  public static function closeInstanceUsingDbName($db_name) {
    if(isset(self::$instances[$db_name])) {
      self::$instances[$db_name]->dbHandle = null;
      unset(self::$instances[$db_name]);
    }
  }

  /**
   * Convience method to verify the key passed in is a valid integer-based ID
   * Typically used for tables with a primary, auto_increment field
   *
   * @param integer $key
   * @return boolean
   */
  public function isKey($key) {
    return (isset($key) && intval($key) > 0 && preg_match('/^[0-9]+$/', $key) ? true : false);
  }

  /**
   * Convience method to PDO query.
   *
   * Note this catches the PDOException, reports the message to the error log,
   * and returns false on any failures.
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   */
  public function query($sql) {
    try {
      $rst = $this->dbHandle->query($sql);
    } catch(\PDOException $e) {
      error_log("ERROR while attempting to query $sql \n Error Thrown was: " . $e->getMessage());
      return false;
    }

    return $rst;
  }


  /**
   * Convience method for issuing a query, and then fetching a single row from
   * a select statement.
   *
   * Note this catches the PDOException, reports the message to the error log,
   * and returns false on any failures.
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   */
  public function fetchRow($sql, $fetch_style = \PDO::FETCH_ASSOC) {
    if($this->debug_mode == true) echo "\n\n$sql\n\n";

    try {
      $rst = $this->query($sql);
      $row = $rst->fetch($fetch_style);
    } catch(\PDOException $e) {
      error_log("ERROR while attempting to fetch a row using: $sql \n Error Thrown was: " . $e->getMessage());
      return array();
    }

    return $row;
  }

  /**
   * Convience method for issuing a query, and then fetchAll for all rows found from
   * a select statement.
   *
   * Note this catches the PDOException, reports the message to the error log,
   * and returns false on any failures.
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   */
  public function fetchRows($sql, $fetch_style = \PDO::FETCH_ASSOC) {

    try {
      $rst = $this->query($sql);
      $rows = $rst->fetchAll($fetch_style);
    } catch(\PDOException $e) {
      error_log("ERROR while attempting to fetch all rows using: $sql \n Error Thrown was: " . $e->getMessage());
      return array();
    }

    return $rows;
  }

  /**
   * Convience method for issuing a query, and then fetchColumn to obtain a specific column for
   * a select statement.
   *
   * Note this catches the PDOException, reports the message to the error log,
   * and returns false on any failures.
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   */
  public function fetchColumn($sql, $column = 0 ) {

    try {
      $rst = $this->query($sql);
      $value = $rst->fetchColumn($column);
    } catch(\PDOException $e) {
      error_log("ERROR while attempting to fetchColumn using: $sql \n Error Thrown was: " . $e->getMessage());
      return array();
    }

    return $value;
  }

  /**
   * Convience method to the PDO prepare method.
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   * @throws PDOException
   */
  public function prepare($sql) {
    return $this->dbHandle->prepare($sql);
  }

  /**
   * Convience method to the PDO begin method for InnoDB transations
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   * @throws PDOException
   */
  public function begin() {
    $this->dbHandle->beginTransaction();
  }

  /**
   * Convience method to the PDO commit method for InnoDB transactions
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   * @throws PDOException
   */
  public function commit() {
    return $this->dbHandle->commit();
  }

  /**
   * Convience method to the PDO rollback method for InnoDB transactions
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   * @throws PDOException
   */
  public function rollback() {
    return $this->dbHandle->rollBack();
  }

  /**
   * Convience method to the PDO lastInsertId method
   *
   * @param string $sql
   * @return mixed Bool|PDOStatement - FALSE on error, or PDOStatement on success
   * @throws PDOException
   */
  public function lastInsertId() {
    return $this->dbHandle->lastInsertId();
  }

  /**
   * For the ID's (array of values that should be integers) passed in, maps the
   * intval method to ensure they are either 0 or the integer that's expected.
   *
   * @param array $ids
   * @return array integer values for all values within the array.
   */
  public function sanitizeIds(array $ids) {
    return array_map('intval', $ids);
  }

  /**
   * Helper method to check wether a the table name passed in ($table) is a valid
   * table for the current instnace being used.
   *
   * @param string $table  Name of the table to check for
   * @return boolean
   */
  public function isValidTable($table) {

    $sql = "SELECT `TABLE_NAME` FROM `information_schema`.`TABLES`
            WHERE `TABLE_SCHEMA` = '" . self::DB_NAME . "'
              AND `TABLE_NAME` = ?";

    try {

      $stmt = $this->dbHandle->prepare($sql);

      $stmt->bindValue(1, $table);
      $stmt->execute();

      $rst = $stmt->fetch(\PDO::FETCH_ASSOC);

    } catch(\Exception $e) {
      error_log("ERROR while attempting to check isValidTable for: $table \n Error Thrown was: " . $e->getMessage());
      return false;
    }

    return (!empty($rst));
  }

  /**
   * Helper method to check wether a the column ($column) name passed in for a
   * table ($table) is a valid column using the current database instance connection.
   *
   * @param string $table  Name of the table to check for
   * @return boolean
   */
  public function isValidColumn($table, $column) {

    $sql = "SELECT `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = '" . self::DB_NAME . "'
              AND `TABLE_NAME` = ?
              AND `COLUMN_NAME` = ?";

    try {
      $stmt = $this->dbHandle->prepare($sql);

      $stmt->bindValue(1, $table);
      $stmt->bindValue(2, $column);
      $stmt->execute();

      $rst = $stmt->fetch(\PDO::FETCH_ASSOC);

    } catch(\Exception $e) {
      error_log("ERROR while attempting to check isValidColumn for: $table using $column \n Error Thrown was: " . $e->getMessage());
      return false;
    }

    return (!empty($rst) ? true : false);
  }

  /**
   * Helper method to obtain table information schema for the supplied table ($table)
   *
   * @param string $table  Name of the table to obtain schema information
   * @return boolean
   */
  public function getTableInformation($table) {

    $table_info = array();

    $sql = "SELECT `COLUMN_NAME`, `DATA_TYPE`
            FROM `information_schema`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = '" . self::DB_NAME . "'
              AND `TABLE_NAME` = ?";

    if($this->isValidTable($table) === true) {

      try {
        $stmt = $this->dbHandle->prepare($sql);

        $stmt->bindValue(1, $table);
        $stmt->execute();

        $rst = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      } catch(\Exception $e) {

        error_log("ERROR while attempting to getTableInformation for: $table \n Error Thrown was: " . $e->getMessage());

      }

      if(!empty($rst)) {

        foreach($rst as $column_info) {
          $table_info[$column_info['COLUMN_NAME']] = $column_info['DATA_TYPE'];
        }

      }
    }

    return($table_info);
  }
}
?>
