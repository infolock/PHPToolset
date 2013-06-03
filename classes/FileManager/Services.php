<?php
/**
 * @static
 * @author Jonathon Hibbard
 *
 * This STATIC class is used for performing validation-type tasks for a file (such as if a file/path exists).
 *
 * @uses
 * MyProject\Utils\Sanitizer
 * Mimetype - Mimetype.php
 *
 * @throws MyProject\FileManager\Exception, \Exception
 */

namespace MyProject\FileManager;

class Services {

  /**
   * Constant to be used for reporting a stat issue (will be shown via sprintf
   * with %s being replaced by the filename having permission issues.)
   */
  const FILE_STAT_FAILED = 'INADEQUATE PERMISSIONS have prevented a filemstime (stat) of the file %s!  The file exists but its permissions must be updated before it will be used!';

  /**
   * @static
   * @access private
   * @var integer
   *
   * Maximum size the server will allow (highest value between filesize and
   * post max size found) for upload file content.
   */
  static private $server_max_byte_size = null;

  /**
   * @static
   * @access private
   * @var integer
   *
   * Max Post Size (in bytes) the server allows
   */
  static private $server_post_max_size = null;

  /**
   * @static
   * @access private
   * @var integer
   *
   * Max Upload Filesize (in bytes) the server allows
   */
  static private $server_upload_max_filesize  = null;

  /**
   * @static
   * @access public
   * @author nak5ive@gmail.com
   *     http://www.php.net/manual/de/function.filesize.php#91477
   *
   * @author Jonathon Hibbard
   *  - Removed precision as a param and made it into a static definition.  2 is more than enough.
   *  - Added phpDoc comments.
   *  - Removed the commented out "alternatives"
   *
   * Converts bytes into a human readable format.
   *
   * @param  integer $bytes
   * @return integer Human-Readable byte representation.
   */
  public static function formatBytes($bytes) {
    $precision = 2;
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }

  /**
   * @static
   * @access public
   * @author PHP.net (http://php.net/manual/en/function.ini-get.php)
   *
   * Translates a human readable value into the byte integer equivalent.
   *
   * @param integer $val
   * @return int
   */
  public static function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
      // The 'G' modifier is available since PHP 5.1.0
      case 'g':
        $val *= 1024;
      case 'm':
        $val *= 1024;
      case 'k':
        $val *= 1024;
    }

    return $val;
  }

  /**
   * @static
   * @access public
   *
   * Gets the post_max_size from the php ini.
   *
   * @return integer self::$server_post_max_filesize
   */
  public static function getServerPostMaxSize(){
    if(!isset(self::$server_post_max_size)) {
      self::$server_post_max_size = self::return_bytes(ini_get('post_max_size'));
    }
    return self::$server_post_max_size;
  }

  /**
   * @static
   * @access public
   * Gets the post_max_size from the php ini.
   *
   * @return integer self::$upload_max_filesize
   */
  public static function getServerMaxFilesize() {
    if(!isset(self::$server_upload_max_filesize)) {
      self::$server_upload_max_filesize = self::return_bytes(ini_get('upload_max_filesize'));
    }
    return self::$server_upload_max_filesize;
  }

  /**
   * @static
   * @access public
   * Gets the server's maximum size of bites allowed for content coming in via POST/File Upload.
   *
   * @return integer self::$server_max_byte_size
   */
  public function getServerMaxByteSize() {
    if(!isset(self::$server_max_byte_size)) {
      $post_max_size       = self::getServerPostMaxSize();
      $upload_max_filesize = self::getServerMaxFilesize();
      self::$server_max_byte_size = ($post_max_size > $upload_max_filesize ? $post_max_size : $upload_max_filesize);
    }
    return self::$server_max_byte_size;
  }

  /**
   * Gets the filemtime of the supplied $filepath if:
   *  - file exists
   *  - is readable
   *  - is_executable
   *
   * @see 2nd Note in the "Notes" section for filemtime (http://us2.php.net/filemtime) if more information is needed.
   *
   * @param  string $filepath  - String representing a filepath to a directory or filename.
   * @param  string $append_string_on_error  - A string to append to the error_log call in the
   *                                           event of improper permissions for filemtime operations.
   *
   * @return integer                         - Returns 0 if there was an error/issue or the filemtime for the supplied filepath.
   */
  public static function getStatForFile($filepath, $append_string_on_error = '') {

    $filepath_stat = 0;

    if(is_file($filepath)) {

      if(is_readable($filepath) && is_executable($filepath)) {
        $filepath_stat = filemtime($filepath);
      } else {
        $append_string_on_error = (isset($append_string_on_error) && is_string($append_string_on_error) ? $append_string_on_error : '');
        error_log(sprintf(self::FILE_STAT_FAILED, $filepath) . $append_string_on_error);
      }
    }

    return $filepath_stat;
  }

  /**
   * @static
   * @access public
   *
   * After sanitizing the filepath passed in, checks to see if the file exists, is readable and writeable.
   * If any of the above fail, returns false.  Otherwise, returns true.
   *
   * For this to fail on a file not existing, $file_exists must be false.  However, if we "want" the file to exist, then
   * $is_new_file = true must be set.
   *
   * @param  string  $filepath     Path to do sanitzation and checks against
   * @param  boolean $file_exists  Will return FALSE if the file exists when this param is set to FALSE (default).
   *                                - Note that FALSE is set when we are wanting to create a file, but want to make sure we aren't overwriting an existing
   *                                  file with the same path information.
   *                               When set to TRUE, will return FALSE if the file does NOT exist.
   * @param  boolean $check_write  When true (default), returns a boolean FALSE if the file is not writable...
   * @return boolean Returns true if all logic passes.  False otherwise.
   */
  public static function isValidFilepath($filepath, $file_exists = false, $check_write = true) {
    $filepath = (is_string($filepath) ? \MyProject\Utils\Sanitizer::sanitizeFilepath($filepath) : '');

    if($file_exists === false) {
      if(empty($filepath) || file_exists($filepath)) {
        return false;
      }
    } else {
      if(empty($filepath) || !file_exists($filepath) || !is_readable($filepath) || ($check_write === true && !is_writable($filepath))) {
        return false;
      }
    }

    return $filepath;
  }

  /**
   * @static
   * @access public
   *
   * Obtains all information about a file that is possible and returns it as a key/value pair array.
   *
   * @param  string  $filename     The filename to obtain information about.
   * @param  boolean $file_exists  When FALSE (default) the file is expected not to exist.  True expects the file TO exist.
   * @return mixed   $file_info    A key/value pair array containing file information.
   *
   * @see
   * $file_info - Check the initial $return_data array structure out to get a sense of what to
   *              expect from the results of this method.
   *
   * @uses
   * Mimetype - File_Manager/Mimetype.php
   */
  public static function getFileInfo($filename, $file_exists = false) {
    $file_info = array("dirname"             => null,
                       "basename"            => null,
                       "filename"            => null,
                       "mimetype"            => null,
                       "filesize"            => 0,
                       "filesystem_mimetype" => null,
                       "errors"              => array(),
                       "warnings"            => array(),
                      );
    $filename = self::isValidFilepath($filename, $file_exists);
    if(false === $filename) {
      return $file_info["errors"] = "File ($filename) does not exist!  FAILED checking mimetype!";
    }

    $file_info['basename'] = basename($filename);
    $file_info['dirname']  = dirname($filename);
    # Get the filesize.
    $file_info['filesize'] = filesize($filename);

    # Attempt to get the mimetype information of the file from the filesystem.
    try {
      $file_info['filesystem_mimetype'] = Mimetype::getForFile($filename, true);
    } catch(Exception $e) {
      # Since it is possible that we may not have access to the filesystem, report this as a WARNING rather an actual error.
      $file_info['warnings'][] = "FAILED obtaining the mimetype for the requested file from the filesystem!";
    }

    # Attempt to get the mimetype information of the file from php's finfo method.
    try {
      $file_info['mimetype'] = Mimetype::getForFile($filename);
    } catch(Exception $e) {
      $file_info['errors'][] = "FAILED obtaining the mimetype for the requested file!";
    }

    return $file_info;
  }

  /**
   * @static
   * @access public
   *
   * Simple helper method to create a directory.
   * Should the directory exist, an exception will be thrown.
   *
   * @param string      $path   Path of the directory we want to create.
   * @throws Exception
   */
  public static function createDir($path) {
    $path = self::isValidFilepath($path);
    if(false === $path || !@mkdir($path)) {
      throw new Exception("Path ($path)", Exception::CREATE_DIRECTORY_ERROR);
    }
  }

  /**
   * @static
   * @access public
   *
   * Removes a filepath from the filesystem.
   *
   * Throws Exceptions in the following situations:
   *  - When the filepath isn't found.
   *  - If the deletion (using php's unlink()) fails
   *
   * @param  string    $filepath
   * @throws Exception
   */
  public function deleteFilepath($filepath) {
    $filepath = self::isValidFilepath($filename, true);
    if(false === $filepath) {
      throw new Exception("filepath ($filepath)!", Exception::FILE_NOT_FOUND);
    } else {
      if(!@unlink($filepath)) {
        throw new Exception("filepath ($filepath)", Exception::DELETE_FILE_ERROR);
      }
    }
  }

  /**
   * @static
   * @access public
   *
   * Attempts to copy a file from one location to another.
   *
   * @param string  $origin                       The filepath we are copying FROM
   * @param string  $destination                  The filepath we are copying TO
   * @param boolean $delete_origin                When true (default), the origin will be deleted after the copy succeeds.
   * @param boolean $delete_existing_destination  When true (default), will delete the destination file if it already exists.
   *
   * @throws Exception
   * @see method comments below for more information on Exceptions thrown.
   */
  public function copyFile($origin, $destination, $delete_origin = true, $delete_existing_destination = false) {
    # sanitize and verify the origin exists.
    $origin = self::isValidFilepath($origin);
    if($origin === false) {
      throw new Exception("Origin: ($origin) (COPY FAILED)", Exception::FILE_NOT_FOUND);
    }

    # sanitize and verify the destination exists.
    $destination = self::isValidFilepath($destination, $delete_existing_destination);
    if($destination === false) {
      throw new Exception("Destination: ($destination) (COPY FAILED)", Exception::FILE_NOT_FOUND);
    }

    # Do we delete the destination if it exists?  Throw an exception if we fail.
    if($delete_existing_destination !== false && false === unlink($destination)) {
      throw new Exception("Destination: ($destination) (COPY FAILED)", Exception::DELETE_FILE_ERROR);
    }

    # Copy the origin filepath to the destination filepath.
    if(false === copy($origin, $destination)) {
      throw new Exception("Origin: ($origin),  Destination: ($destination)", Exception::COPY_FILE_ERROR);
    }

    # Should we delete the origin after the copy is finished?
    if(true === $delete_origin && false === unlink($origin)) {
      throw new Exception("Origin: ($origin) (NOTE: COPY SUCCEEDED)", Exception::DELETE_FILE_ERROR);
    }
  }

  /**
   * @static
   * @access public
   *
   * Generates a random filename (usually used for fileuploads when dealing with streams)
   *
   * @param  string $ext  // The extension of the file to apply to the new filename
   * @return string       // Returns the new filename
   */
  public static function generateTmpFilename($ext = 'txt') {
    return md5(time() . mt_rand()) . "." . $ext;
  }

  /**
   * @static
   * @access public
   *
   * Returns the Source contents of the file passed in.
   * Throws an exception if not found or fails.
   *
   * @param string $filepath
   * @throws Exception
   */
  public static function getSource($filepath) {
    $filepath = self::isValidFilepath($filepath, true);
    # Verify we have a valid filepath and that it is, indeed, a file!
    if(false === $filepath || !is_file($filepath)) {
      throw new Exception("filepath ($filepath)", Exception::FILE_NOT_FOUND);
    }

    return file_get_contents($filepath);
  }
}
?>