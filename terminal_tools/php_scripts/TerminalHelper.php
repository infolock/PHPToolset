<?php
/**
 * @author Jonathon Hibbard
 *
 * Set of terminal helper methods for scripts being build to run in CLI mode.
 *
 * @throws Exception
 */
class TerminalHelper {
  private static $usage_help_information = "\nSorry - No Usage Help Information was found for this tool.\n";
  private static $getopt_params = array();
  /**
   * Show a message and prompt the user for a Y or N answer.
   *
   * @param  string $question - The question to ask
   * @param  string $callback - (OPTIONAL) String form of method to send the answer to.
   * @return Boolean $answer  - Returns TRUE if the user entered Y, FALSE if N was recieved.
   * @throws Exception - Thrown only when the optional $callback is supplied, but the method is not found.
   *
   * @example (Without Callback)
   * TerminalHelper::promptUser("Are you sure you want to continue?");
   *
   * @example (With Callback)
   * function myPromptUserCallbackMethod($answer) {
   *   if($anwer == true) {
   *     // Nuke the directory?  Show a smiley face?
   *   } else {
   *     // They answerd no?  WHAT?  BLAST!  TRASH THE DRIVE!  Or...show a sad face?
   *   }
   * }
   * TerminalHelper::promptUser("Are you sure you want to continue?", 'myPromptUserCallbackMethod');
   */
  public static function promptUser($question, $callback = null) {
    echo $question . " (Y/N) : ";
    $prompt = fopen ("php://stdin","r");
    $answer = trim(strtolower(fgets($prompt)));

    if(empty($answer) || !in_array($answer, array("y","n"))) {
      echo "\nPlease Answer with y or n (You answered with $answer)\n";
      $answer = self::promptUser($question);
    }

    $answer = ($answer == 'y' ?: false);
    if(isset($callback)) {
      if(function_exists($callback)) {
        call_user_func($callback, $answer);
      } else {
        throw new Exception("The Callback (" . (string)$callback . ") Function could not be found!");
      }
    }

    return $answer;
  }

  public static function setGetOptParams(array $params) {
    self::$getopt_params = getopt(implode('', array_keys($params)), $params);
  }

  public static function getOptArray() {
    return self::$getopt_params;
  }

  public static function shortOptionIsset($short_name) {
    return (isset(self::$getopt_params[$short_name]) ?: false);
  }

  public static function longOptionIsset($long_name) {
    return (isset(self::$getopt_params[$long_name]) ?: false);
  }

  public static function optionIsset($short_name, $long_name) {
    return (true == self::shortOptionIsset($short_name) || true == self::longOptionIsset($long_name));
  }

  public static function valueFromGetParamsWithShortName($short_name) {
    return (isset(self::$getopt_params[$short_name]) ? self::$getopt_params[$short_name] : null);
  }

  public static function valueFromGetParamsWithLongName($long_name) {
    return (isset(self::$getopt_params[$long_name]) ? self::$getopt_params[$long_name] : null);
  }

  /**
   * Typically, its a good idea to type out your help information in a separate README or HELP file.
   * If you do, call this method to load the information from it.
   *
   * Question: Why is it a good idea to put it into a separate file?
   * Answer: Removes unecessary clutter from the source code that does something useful.  Code cleanliness.
   *
   * @param type $path_to_usage_help_information_file
   * @throws Exception
   */
  public static function setUsageHelpInformationFromFile($path_to_usage_help_information_file) {
    if(file_exists($path_to_usage_help_information_file)) {
      $usage_help_information = file_get_contents($path_to_usage_help_information_file);
      if(false == $usage_help_information) {
        throw new Exception("FAILED to load the Usage Help Information from file!");
      }
      self::setUsageHelpInformation();
    }
  }

  public static function setUsageHelpInformation($usage_help_information) {
    self::$usage_help_information = $usage;
  }

  public static function getUsageHelpInformation() {
    return self::$usage_help_information;
  }

  public static function showUsageHelpInformation($shouldExit = false) {
    echo self::getUsageHelpMessage();
    if($shouldExit !== false) {
      exit(0);
    }
  }
}
?>