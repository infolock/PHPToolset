<?php
/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2013 Jonathon Hibbard
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class NinjaAPISecurity {

  const N_PEPPER = 'SOMEONE_SET_US_UP_WITH_THE_BOMB!';

  /**
   * @static
   * @access private
   * The public key that is passed to/from client requests (along with the salt).  Just an example.  You don't (and shouldn't) just keep it here...
   */
  private static  $public_key = 'asdfasdfasdfasdfasdf';

  /**
   * Yeah... change this.
   */
  private getUserInfoForUserId($user_id) {
    return array("user_id" => $user_id, "username" => "happy", "password" => md5("gilmorefeet"), "generated_key" => md5(self::$public_key . time()));
  }

  /**
   * Yeah... change this.
   */
  private getSharedSecretForUserId($user_id) {
    // ....
    // do whatever to get the value you're exepcting.
    // because this is just an example...
    $user_info = self::getUserInfoForUserId($user_id);
    return md5($user_id . $user_info['generated_key'] . $user_info['password']); 

  }

  /**
   * @static
   * @access private
   *
   * @param  string $salt 
   *
   * @throws Exception when $salt is not a string or is empty.
   */
  private static verifySalt($salt) {
    if(!is_string($salt) || empty($salt)) throw new Exception("The Salt must be a valid string!");
  }

  /**
   * @static
   * @access private
   *
   * @param  integer  $count 
   * @param  integer  $min_iteration_size 
   *
   * @throws Exception when the iteration count recieved is less than the minimum iteration size allowed.
   */
  private static function verifyIterationCount($count, $min_iteration_size = 1024) {
    if(intval($count) < $min_iteration_size) throw new Exception("Count must be at least $min_iteration_size ...");
  }

  /**
   * @static
   * @access private
   *
   * @param  integer  $key_length 
   * @param  integer  $min_length 
   * @param  integer  $max_length 
   *
   * @throws Exception when the $key_length recieved does not equal either the min or max length expected.
   */
  private static function verifyKeyLength($key_length, $min_length = 16, $max_length = 32) {
    if(intval($key_length) != $min_length && intval($key_length) != $max_length) throw new Exception("The key length must either be $min_length or $max_length32!");
  }

  /**
   * @static
   * @access private
   *
   * @param  string  $algorithm 
   * @param  string  $shared_secret
   * @param  string  $salt 
   *
   * @return string  $dk - Returns the derived key needed to complete the hash.
   */
  private static function derivedKeyUsingAlgorithmWithSalt($algorithm, $shared_secret, $salt) {
    # Derived key
    $dk = '';
    # Create key
    for($block = 1; $block <= $kb; $block ++ ) {
      # Initial hash for this block
      $ib = $b = hash_hmac($algorithm, $shared_secret . pack('N', $block), $salt, true);
      # Perform block iterations and XOR each iterate
      for($i = 1; $i < $count; $i ++) $ib ^= ($b = hash_hmac($algorithm, $b, $salt, true));
      # Append iterated block
      $dk .= $ib;
    }
    return $dk;
  }

  /**
   * @author Andrew Johnson
   * @source http://www.itnewb.com/tutorial/Encrypting-Passwords-with-PHP-for-Storage-Using-the-RSA-PBKDF2-Standard
   * PBKDF2 Implementation (described in RFC 2898 - http://www.ietf.org/rfc/rfc2898.txt)
   *
   * .: REQUIRED PARAMS :.
   * @param string  $shared_secret
   * @param string  $salt
   * .: OPTIONAL PARAMS :.
   * @param int     $count
   * @param int     $key_length
   * @param boolean $return_as_base64
   *
   * @return string The pbkdf2 value in binary or base64 (based on $return_as_base64)...
   * @throws Exception
   *
   * ------------------------------------------------------------------------------------
   *
   * @see http://en.wikipedia.org/wiki/PBKDF2 for more information
   * 
   *
   * @edit Jonathon Hibbard
   *  Rather than treat this as a password hashing tool, I turned this into a way to create a way of adding a *tad* stronger 
   *  mechanism to verifying API consisting of a private key, public key, and salt to create a secure combination key.\
   *
   *  @param $shared_secret  - This replaces the $password from the original version of this method.
   *                           It is typically something only you and the user know.  This value 
   *                           NEVER appears by itself in a request to your api.  rather, the user will use the shared_secret on their end
   *                           to generate the HMAC that you will be verifying, and you will use a local, Stored version of the shared secret (such as 
   *                           an md5 of their password, user id and first_name) to validate that they are using the correct combinations for the HMAC they have presented.
   *  
   *   Misc Changes/Information
   *   - Split out the operations into their own private methods
   *   - Added an inline return
   *   - Added Exceptions to be thrown when stuff goes wrong - or recieves things that were expected.
   *   - Added ability to specify the min/max lengths expected for various properties.
   *   - Added validations to the items being recieved to the method via the private methods attached.
   *   - Removed the ability to define the algorithm to use, and set it to be, by default, sha1.  *shrug*, put it back in place if you want.
   */
  protected function PBKDF2($shared_secret, $salt, $count = 1024, $key_length = 16, $return_as_base64 = true) {
    # force sha1 as the algorithm.
    $algorithm = 'sha1';

    $this->verifySalt($salt);
    $this->verifyIterationCount($count);
    $this->verifyKeyLength($key_length);

    $dk = $this->derivedKeyUsingAlgorithmWithSalt($algorithm, $shared_secret, $salt);
    # Hash length
    $hl = strlen(hash($algorithm, null, true));
    # Key blocks to compute
    $kb = ceil($key_length / $hl);

    # Return derived key of correct length
    $pbkdf2_hash = substr($dk, 0, $key_length);
    return ($return_as_base64 === true) ? base64_encode($pbkdf2_hash) : $pbkdf2_hash;
  }


  /**
   * @example
   * $user_id = intval($_GET['user_id']);
   * $salt = md5($_SERVER['SERVER_ADDR']);  // this is a really bad idea - and yes, it is JUST an example...
   * $unverified_HMAC = urldecode($_POST['hmac_signature']);
   * if(APIValidationNinja::isValidHMAC($user_id, $salt, $unverified_HMAC)) {
   *   // FIREWORKS - YAY!
   * } else {
   *   // BOOOOM!  FAILURE!
   * }
   */
  public static function isValidHMAC($user_id, $salt, $unverified_HMAC) {
    $shared_secret = self::getSharedSecretForUserId($user_id);
    $salt = self::getSalt();
    $proper_HMAC = self::PBKDF2($shared_secret, $salt);

    return $proper_HMA == $unverified_HMAC;
  }
}
?>
