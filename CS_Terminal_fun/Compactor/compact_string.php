<?php
/**
 * This script takes in an argument of the path to a file, of which its string contents are
 * to be compacted and then sent to the standard out once finished.
 * 
 * The following requirements have been applied to any strings found within the source:
 *    [x] A function created to compact a string (per first requirement)
 *    [x] All Whitespace are removed from the contents of the string.
 *    [x] Remove duplicate characters if they are next to each other
 *    [x] Outputs the results to standard out
 * 
 * This script is intended to be run from the command line.  With that being the case,
 * the following checks must pass before the script will execute:
 *    [x] register_argc_argv must be enabled and exist within the current php configuration.
 *    [x] A valid filename must be passed to the script (Represented with $argv[1]) along with read permissions.
 * 
 * @author Jonathon Hibbard
 * @copyright Copyright (c) 2012, Jonathon Hibbard
 * @example
 * php compact_source_strings.php /path/to/source_file.txt
 * 
 * @filesource
 */

# Try to get the register_argc_argv setting in the php.ini...
$is_argv_enabled = ini_get("register_argc_argv");

# If ini_get returned a false, empty, or 0, we won't have access to $argv, so exit.
if(empty($is_argv_enabled)) {
  exit;
}

# Make sure they actually passed anything to our script.  Otherwise, exit.
if(!isset($argv) || !isset($argv[1])) {
  exit;
}

# Get the argument passed.  We'll call it filename here as that is the assumed value.
$filename = trim($argv[1]);

# If we don't have a valid, non-empty file, just exit.
if(!is_file($filename) || !is_readable($filename) || filesize($filename) == 0) {
  exit;
}

# Load the contents.  Var here in case we need to do something else with the contents later on.
$string_to_compact = file_get_contents($filename);

/**
 * This method compacts a string as explained in the documentation above (see lines 
 * 7-10 for more information)
 * 
 * @param  string  $string_to_compact  The string we want to compact
 * @param  boolean $remove_newlines    (OPTIONAL)
 *                                     By default (false), replace only whitespace.
 *                                     When set to to true, replaces both whitespaces and newlines.
 * 
 * @return string  $compacted_string   Returns the compacted string as per documented rules applied above.
 */
function compact_string($string_to_compact, $remove_newlines = false) {
  /**
   * Find any repeating characters and use backtrace to use with our replacement.
   * 
   * Source
   * http://randomdrake.com/2008/04/10/php-and-regex-replacing-repeating-characters-with-single-characters-in-a-string/
   */
  $match_patterns = array('{(.)\1+}');

  # Do we want to replace newlines?  If not, we replace only whitespace (default)
  if($remove_newlines === false) {
    $match_patterns[] = '/\s+/';
  } else {
    $match_patterns[] = '/[\s\r\n\p]+/';
  }

  # Define what to replace each pattern's match with...
  $replace_with = array('$1', '');

  # Return our results!
  return preg_replace($match_patterns, $replace_with, $string_to_compact);
}

/**
 * Compact the string and then echo out the results.
 * @see compact_string()
 */
echo compact_string($string_to_compact) . "\n";
?>