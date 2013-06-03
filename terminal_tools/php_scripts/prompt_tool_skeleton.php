#!/usr/bin/php
<?php
/**
 * @author Jonathon Hibbard
 *
 * Just an example script that can accept arguments, and also can prompt the
 * user for y/n input before continuing execution...
 *
 * Note: This skeleton takes advantage of the TerminalHelper class found within
 * this directory.  Please see it for more information on some of the method calls to it below.
 *
 * @uses TerminalHelper - TerminalHelper.php
 */

include_once("TerminalHelper.php");

// Defines the available params.  See http://www.php.net/getopt for more information.
$available_params = array(
  // Required Value
  "m:" => "message:",
  // Optional Value
  "n::" => "answersWithNo::",
  // Optional Param
  "h" => "help",
);

# Load the options...
TerminalHelper::setGetOptParams($available_params);

if(TerminalHelper::optionIsset('-h', '--help')) {
  TerminalHelper::showUsageHelpInformation(true);
}

echo "\nStarting...\n\n";

$isHelpful = TerminalHelper::promptUser("Hey, is this helpful at all?");
if($isHelpful == true) {
  echo "\nOk.  Next Question...\n";

  TerminalHelper::promptUser("Would you like to exit now?", function($shouldExit) {
    if($shouldExit) {
      echo "\nBye!\n";
      exit(0);
    } else {
      $message = TerminalHelper::optionIsset('n', 'answersWithNo');
      if($message == false) {
        $message = "....too bad.\n";
      }
      echo $message;
      exit(0);
    }
  });
} else {
  echo "\n...k.\n";
}

echo "\nAll done here!  Thanks!\n";
exit(0);
?>