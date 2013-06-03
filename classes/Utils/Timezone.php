<?php
/**
 * @author Jonathon Hibbard
 *
 * Timezone class for translating to/from timezones.
 */
namespace MyProject\Utils;
class Timezone {
  /**
   * The constants below are helpers for requesting a specific timezone.
   *
   * The timezone values assigned to these constants are structured to meet the
   * required structures for PHP's DateTime object.
   *
   * If a needed timezone is not found in this list, please create a constant
   * for that timezone.
   *
   * A complete list of supported timezones can be found in the PHP manual.
   *
   * @see http://us2.php.net/manual/en/timezones.php
   */

  // UTC Time
  const DEFAULT_TZ = 'UTC';
  // Alternate Way of getting the UTC Time
  const UTC  = 'UTC';
  /**
   * Atlantic Time
   */
  const AST = 'America/Glace_Bay';
  /**
   * Eastern Time
   */
  const EST = 'America/New_York';
  /**
   *  Central Time (US & Canada)
   */
  const CST = 'America/Chicago';
  /**
   *  Mountain Time
   */
  const MST = 'America/Denver';
  /**
   * Arizona MST
   */
  const AZST = 'America/Phoenix';
  /**
   *  Pacific Time
   */
  const PST = 'America/Los_Angeles';
  /**
   *   Alaska
   */
  const AKST = 'America/Anchorage';
  /**
   *  Hawaii-Aleutian Standard Time
   */
  const HAST = 'America/Adak';
  /**
   *  Hawaii
   */
  const HST = 'Etc/GMT+10';

  // This holds the current timezone found as the server's setting.
  private static $server_timezone   = null;
  // This holds the server's UTC offset (if a timezone was set).
  private static $server_utc_offset = null;

  /**
   * Converts a timestamp from a timezone ($from_timezone) TO another time zone ($to_timezone).
   *
   * @param  integer||string  $timestamp      // The timestamp we want to convert (Either a date (ie: 2008-11-11 08:55 pm, etc.) or Unix Timestamp (123123123)...
   * @param  string           $from_timezone  // The timezone the $timestamp was generated FROM (ie: America/New_York)
   * @param  string           $to_timeszone   // The timezone we want $timestamp to be converted TO (ie: Etc/UTC)
   * @return integer          $new_timestamp  // Returns the new timestamp after conversion.
   *
   * @see http://www.php.net/datetime
   */

  public static function convertTimestamp($timestamp, $from_timezone, $to_timezone) {
    $timestamp = trim($timestamp);

    // If we didn't get a unixtimestamp (integer), then we'll run strtotime to get it instead.
    if(!preg_match("/^[0-9]+$/", $timestamp)) {
      $timestamp = strtotime($timestamp);
    } else {
      // intval it here to ensure we have an integer...
      $timestamp = intval($timestamp);
    }

    // Make sure to get the datetime string.  This is needed to convert properly...
    $datetime_string = date("Y-m-d H:i:s", $timestamp);

    // Setup our timezone objects
    $fromTimeZoneObject = new \DateTimeZone($from_timezone);
    $toTimeZoneObject   = new \DateTimeZone($to_timezone);

    // Convert it!
    $dateTimeObj = new \DateTime($datetime_string, $fromTimeZoneObject);
    $offset = $toTimeZoneObject->getOffset($dateTimeObj);

    // Get the new unix timestamp.
    $new_datetime_string = date('Y-m-d H:i:s', $dateTimeObj->format('U') + $offset);

    // Return the integer unix timestamp.
    return strtotime($new_datetime_string);
  }

  /**
   * @access private
   *
   * When called, sets the private $server_timezone variable to whatever the server's
   * settings are.
   *
   * return void
   */
  private static function setServerTimezone() {
    if(!isset(self::$server_timezone)) {
//      self::checkServerTimezone();
      $tz = ini_get('date.timezone');
      if(empty($tz)) {
        error_log("A timezone was not found in php.ini.  Setting and Using " . self::DEFAULT_TZ . " as default.  (Reported in " . __CLASS__ . " on line " . __LINE__ . ")");
        $tz = self::DEFAULT_TZ;
        self::setServerTimezone();
      }
      self::$server_timezone = $tz;
    }
  }

  /**
   * This will return the current UTC timestamp.
   *
   * @return integer // Returns a UTC unixtimestamp for current time.
   */
  public static function getUtcTimestamp() {
    self::setServerTimezone();
    $date = new \DateTime(null, new \DateTimeZone('UTC'));
    return $date->getTimestamp();
  }

  /**
   * Helper method for PHP's Datetime Object for obtaining the offset of a supplied timezone (and optionally apply to the supplied $date).
   *
   * NOTE -- If the optional $date param is passed, the offset will be based on it.
   *
   * @param type $timezone
   * @param type $date
   * @return type
   * @throws \Exception
   *    An exception will be thrown in the following cases:
   *      [*] If $date is supplied but not in the YYYY-MM-DD format
   *      [*] If a new DateTime Object cannot be created due to an invalid/Unknown Timezone being passed.
   *
   * @see http://us2.php.net/manual/en/timezones.php to see a list of timezones supported by PHP's DateTime
   *      object, or use one of the constant definitions avilable above for this class for common constants
   *      in use.
   */
  public function getTimezoneOffset($timezone, $date = NULL) {
    if(isset($date) && !preg_match(\MyProject\Utils\Sanitizer::PATTERN_PHP_YYYMMDD_DATE_FORMAT, $date)) {
      throw new \Exception('Invalid date(value is ' . $date . ') recieved in getOffsetForTimezone in ' . __CLASS__ . ' on Line ' . __LINE__ . '!  This method only accepts dates in the format of YYYY-DD-MM Format.');
    } else {
      $date = date('Y-m-d');
    }

    $dateTimeObj = new \DateTime($date, new \DateTimeZone($timezone));
    if(false === $dateTimeObj) {
      throw new \Exception('Invalid timezone (' . $timezone . ') and/or date (' . $date . ')!  Could not determine timezone offset.  Thrown in ' . __CLASS__ . ' on Line ' . __LINE__);
    }

    return $dateTimeObj->getOffset();
  }

  /**
   * Returns the current timezone the server is set to.
   *
   * @return integer  // self::$server_timezone
   */
  public static function getServerTimezone() {
    self::setServerTimezone();
    return self::$server_timezone;
  }

  /**
   * Gets the server's current UTC offset.
   *
   * @return integer  // Returns the server's UTC Offset.
   */
  public static function getServerUTCOffset() {
    if(!isset(self::$server_utc_offset)) {
      self::$server_utc_offset = date("Z");
    }
    return self::$server_utc_offset;
  }

  /**
    * Function will take the current time string - in format of strtotime and convert it to Unix timestamp based on a given timezone
    *
    * @param string $time - time to translate
    * @return int - unix timestamp
    *
    * @throws Exception on DateTime failure
    */
  public static function getUnixTimestamp($time = null, $timezone = '(GMT -5:00) Eastern Time (US &amp; Canada)') {
    if (!is_string($time) || is_null($time)) {
      throw new \InvalidArgumentException("Time must be a string");
    }

    try {
      $date = new \DateTime($time, new \DateTimeZone($timezone));
      $unix_timestamp = $date->format('U');
    } catch (Exception $e) {
      throw $e;
    }

    return $unix_timestamp;
  }
}
?>
