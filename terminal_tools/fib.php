#!/usr/bin/php -q
<?php
function multi_array_search($search_value, array $search_array) {
 foreach($search_array as $key => $value) {
   if(is_array($value)) {
     # We have an array, so let's recursively search the subarray values
     if(multi_array_search($search_value, $value) === true) {
       return true;
     }
   } else {
     # We have a string, so let's compare and see if we have a match
     if($value == $search_value) {
       return true;
     }
   }
 }
 // Return false since nothing was found above (if something was, it would have returned true).
 return false;
}

$example_array = array("5", "me" => array("ok" => "there"), "me" => array(), "apple" => "orange", array("test" => array("mY" => "foo")));
echo (true === multi_array_search("food", $example_array) ? "YES!\n" : "NO!\n");
?>
