<?php
/**
 * This script takes in an argument of the path to a file, of which its string contents are
 * to be shown in the standard out in spiral form.
 * 
 * The following requirements have been applied to any strings found within the source:
 *    [x] A function created to print a 2-D array (n x m) in spiral order (clockwise)
 * 
 * Assumptions:
 * Per the specification, the array will be presented in the following form: 
 * 1 2 3
 * 
 * With each "key" being represented as the next line with spaces separating each value.
 * 
 * This script is intended to be run from the command line.  With that being the case,
 * the following checks must pass before the script will execute:
 *    [x] register_argc_argv must be enabled and exist within the current php configuration.
 *    [x] A valid filename must be passed to the script (Represented with $argv[1]) along with read permissions.
 * 
 * @author Jonathon Hibbard
 * @copyright Copyright (c) 2012, Jonathon Hibbard
 * @example
 * php show_spiral.php /path/to/source_file.txt
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

/**
 * Returns a space-delimited string containing all keys found within the passed array.
 * 
 * @param  array    $array     The array to obtain the keys from.
 * @param  string   $delim     (OPTIONAL) Defines the delimiter (space by default) for string separation.
 * @param  boolean  $reverse   (OPTIONAL) When false (default), returns the keys in ASC order.
 *                             When true, returns the keys in DESC order.
 *
 * @return string              Returns all found keys in the array delimited by spaces.
 */
function get_all_keys_as_string(array $array, $delim = "", $reverse = false) {
  if($reverse == true) {
    rsort($array);
  }
  return implode(" ", $array) . " ";
}

/**
 * Returns a space-delimited string containing the first available index
 * keys of the array passed.
 * 
 * @param  array    $array     The array to obtain the keys from.
 * @param  string   $delim     (OPTIONAL) Defines the delimiter (space by default) for string separation.
 * @param  boolean  $reverse   (OPTIONAL) When false (default), returns the keys in ASC order.
 *                             When true, returns the keys in DESC order.
 *
 * @return string              Returns all the first found index keys of the array delimited by spaces.
 */
function get_first_keys_as_string(array $array, $delim = "", $reverse = false) {
  # Is a reversed result being requested?
  if($reverse == true) {
    rsort($array);
  }

  # Will house all first position keys found within each array.
  $key_collection = array();
  foreach($array as $key) {
    # Make sure we're removing all the ugly stuff...
    $key_collection[] = trim(preg_replace('/[\s]+/', '', array_shift($key)));
  }

  return implode(" ", $key_collection) . " ";
}

/**
 * Returns a space-delimited string containing the last available index
 * keys of the array passed.
 * 
 * @param  array    $array     The array to obtain the keys from.
 * @param  string   $delim     (OPTIONAL) Defines the delimiter (space by default) for string separation.
 * @param  boolean  $reverse   (OPTIONAL) When false (default), returns the keys in ASC order.
 *                             When true, returns the keys in DESC order.
 *
 * @return string              Returns all the last found index keys of the array delimited by spaces.
 */
function get_last_keys_as_string(array $array, $delim = " ", $reverse = false) {
  if($reverse == true) {
    rsort($array);
  }
  $key_collection = array();
  foreach($array as $key) {
    $key_collection[] = trim(preg_replace('/[\s]+/', '', array_pop($key)));
  }
  return implode($delim, $key_collection) . $delim;
}

/**
 * Factory method to decide which direction we're going to get our 
 * values.  Operations on the passed array are done by reference to reduce unecessary
 * copying.
 * 
 * @note: $direction is Right, Down, Left, Up.  We keep track of this as we're going in a 
 * clockwise motion, following the pattern of the spiral.
 * 
 * @param  array   $array_to_spiral  Reference to the original $array_to_spiral array.
 * @param  string  $direction        The direction we're travelling through the array to get values.
 *                                   Possible Values: Right, Left, Down, Up
 * @param  string  $delim            (OPTIONAL) Defines the delimiter (space by default) for string separation.
 * 
 * @return string                    Returns a string for the direction requested to move through the array...
 */
function get_spiral_string_by_direction(array &$array_to_spiral, $direction, $delim = " ") {
  $direction = strtolower($direction);
  if(!in_array($direction, array("right","down","left","up"))) {
    exit;
//    throw new Exception("Invalid direction passed to " . __FUNCTION__ . "!");
  }
  $spiral_string = '';
  switch($direction) {
    case 'right':
      # Get the string and, by doing so, remove the key we're working on.
      $spiral_string = get_all_keys_as_string(array_shift($array_to_spiral), $delim);
    break;
    case 'down':
      $spiral_string = get_last_keys_as_string($array_to_spiral, $delim);

      # Remove the children since we're finished with them...
      array_walk($array_to_spiral, function(&$key) {
        array_pop($key);
      });
    break;
    case 'left':
      # Get the string and, by doing so, remove the key we're working on.
      $spiral_string = get_all_keys_as_string(array_pop($array_to_spiral), $delim, true);
    break;
    case 'up':
      $spiral_string = get_first_keys_as_string($array_to_spiral, $delim, true);

      # Remove the children since we're finished with them...
      array_walk($array_to_spiral, function(&$key) {
        array_shift($key);
      });
    break;
  }

  return $spiral_string;
}

/**
 * Takes in a 2-Dimensional array and generates a spiral string.  For more information, please
 * refer to the documenation above.
 *
 * @param  array    $array_to_spiral   Defines the array we want to generate a spiral string from.
 * @param  string   $delim             Defines the delimiter to apply to our values in the string. (defaults to spaces)
 * 
 * @return string                      String representing the spiral results.
 */
function generate_spiral(array $array_to_spiral, $delim = " ") {
  $x = count($array_to_spiral);
  # If we don't have more than 1 row, we can stop here...
  if($x === 1){
    return get_all_keys_as_string($array_to_spiral);
  }

  # If we don't have the required dimensions of our array, then we stop.
  if(count($array_to_spiral[0]) != $x) {
    # Really want throw an exception here.... but we'll exit to keep it quiet :)
//    throw new Exception("Invalid 2-D array!  Row and Column counts do not match.  Exiting...");
    exit;
  }

  # this is what will be returned.
  $output = '';

  # Define our directions (which is a clockwise spiral)
  $direction = array("right","down","left","up");

  # Do this until we have nothing left!
  while(!empty($array_to_spiral)) {
    # Do the spiral..
    $output .= get_spiral_string_by_direction($array_to_spiral, current($direction), $delim);
    # Go to the next direction until we finish the loop or the array runs dry.
    if(false === next($direction)) {
      // No more directions so reset our pointer.
      reset($direction);
    }
    # Do a dance, and start it again.
  }
  # return a trimmed result (in case we added spaces in front/back)
  return trim($output);
}

# Get the argument passed.  We'll call it filename here as that is the assumed value.
$filename = trim($argv[1]);

# If we don't have a valid, non-empty file, just exit.
if(!is_file($filename) || !is_readable($filename) || filesize($filename) == 0) {
  exit;
}

# Load the contents.  Var here in case we need to do something else with the contents later on.
$file_contents = file_get_contents($filename);

# Find all newlines are unix newline and remove guess work.
$file_contents = preg_replace('/[\r\n]+/', "\n", $file_contents);

# Houses the array we'll use to spiral...
$array_to_spiral = array();

# We load all lines within the source into an array so we can iterate over them.  We do this per doc requesting a 2-D array...
$source_as_array   = explode("\n", $file_contents);

# Make sure we don't have any blank lines and, if we do, just skip them from being added.
$x = count($source_as_array);
for($i = 0; $i < $x; $i++) {
  if(true === is_numeric($source_as_array[$i]) || !empty($source_as_array[$i])) {
    $array_to_spiral[] = explode(" ", trim(preg_replace('/[\s]+/', ' ', $source_as_array[$i])));
  }
}

/**
 * Generate the spiral!
 * @see generate_spiral
 */
echo generate_spiral($array_to_spiral) . "\n";
?>