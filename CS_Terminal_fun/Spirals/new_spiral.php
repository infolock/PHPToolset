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

# We load all lines within the source into an array so we can iterate over them.  We do this per doc requesting a 2-D array...
$source_as_array   = explode("\n", $file_contents);
$source_chars_array = array();
foreach($source_as_array as $key) {
  if(!empty($key)) {
    $source_chars_array[] = explode(" ", trim($key));
  }
}

/**
 * Validates we have an array which can have a spiral created from it.
 * 
 * @param  array    $source_chars
 * @return boolean  Returns TRUE if the array can have a spiral generated from it, false if not.
 */
function is_valid_spiral_array(array $source_chars) {
  if(empty($source_chars)) {
    return false;
  }

  $size_to_check = count($source_chars[0]);
  foreach($source_chars as $key) {
    if(count($key) < $size_to_check) {
      return false;
    }
  }
  return true;
}

/**
 * Recursively loops through an array passed in to generate a string in spiral form.
 * 
 * @example:
 * $spiral_array = array(array("1","2","3"),
 *                       array("4","5","6"),
 *                       array("7","8","9"),
 *                      );
 * $spiral_string = generate_spiral($spiral_array);
 * echo $spiral_string;
 * // Results in 1,2,3,6,9,8,7,5,8
 * 
 * @param  array  $array     An array to create a spiral string from.
 * @return string $results   Returns a string in spiral form
 */
function generate_spiral(array $array) {
  # The result string to return.
  $result = '';

  # Make sure we have an array to reduce warnings/notices
  if(!empty($array)) {
    # Generate a space-delimited string from the first key's subkeys.
    $result .= implode(" ", array_shift($array)) . " ";
  } else {
    # nothing here, so return $result
    return $result;
  }

  # Make sure we have an array to reduce warnings/notices
  if(!empty($array)) {
    # Loop over the keys in the array and get the last subkey of each, and append each key to the $result (moves down in the spiral)
    $x = count($array);
    for($i = 0; $i < $x; $i++) {
      $result .= array_pop($array[$i]) . " ";
    }
  } else {
    # nothing here, so return $result
    return $result;
  }

  # Make sure we have an array to reduce warnings/notices
  if(!empty($array)) {
    # Generate a space-delimited string from the last key's subkeys in the remaining array. (moves us backwards in the spiral)
    $result .= implode(" " , array_reverse(array_pop($array))) . " ";
  } else {
    # nothing here, so return $result
    return $result;
  }

  # Make sure we have an array to reduce warnings/notices
  if(!empty($array)) {
    # Reverse loop through the keys and get the first key of each iteration (which moves us up the spiral) and append each to the string.
    for($i = count($array) - 1; $i >= 0; --$i) {
      $result .= array_shift($array[$i]) . " ";
    }

    # Do we still have an array?
    if(!empty($array)) {
      # Then start over with the remaining array
      $result .= generate_spiral($array);
    }
  }

  return substr($result, 0, -1);
}

/**
 * Generate a spiral string of the arrays from  our file.
 * 
 * @see generate_spiral()
 */
echo generate_spiral($source_chars_array) . "\n";
?>