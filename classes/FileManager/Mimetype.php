<?php
/**
 * @author Jonathon Hibard
 * This is a STATIC mimetype helper class that can do all of the following:
 *   - Obtain a list of supported mimetypes we allow
 *   - Obtain a list of mimetypes for a specific file_type
 *   - Get the file_type of a mimetype
 *
 * @throws MyProject\FileManager\Exception, \Exception
 */
namespace MyProject\FileManager;
class Mimetype {
  /**
   * @static
   * @access private
   *
   * List of extensions and their mimetype counter parts.
   *
   * @var array
   */
  private static $extensions = array('jpg' => array('image/jpeg', 'image/pjpeg', 'image/jpg'),
                                     'gif' => array('image/gif'),
                                     'png' => array('image/png'),
                                     'css' => array('text/css','text/plain'),
                                     'txt' => array('text/plain'),
                                     'xml' => array('text/xml'),
                                     'rss' => array('application/rss+xml', 'application/rss+xml; charset=ISO-8859-1'),
                                     'swf' => array('application/x-shockwave-flash'),
                                     'pdf' => array('application/pdf'),
                                     'mp3' => array('audio/mpeg', 'audio/x-mpeg', 'audio/mp3', 'audio/x-mp3', 'audio/mpeg3', 'audio/x-mpeg3', 'audio/mpg', 'audio/x-mpg', 'audio/x-mpegaudio'),
                                    );

  /**
   * @static
   * @access private
   *
   * List of supported file type exstions.
   *
   * @var array
   */
  private static $file_type_extensions = array('image'       => array('jpg','jpeg','gif','png'),
                                               'css'         => array('css'),
                                               'text'        => array('txt','log'),
                                               'feed'        => array('xml','rss'),
                                               'application' => array('swf','pdf'),
                                               'flash'       => array('swf'),
                                               'pdf'         => array('pdf'),
                                               'media'       => array('mp3'),
                                              );
  /**
   * @access private
   *
   * List of all supported mimetypes.  The keys of this array are considered the "file_type" for the mimetype
   *
   * @var array
   */
  private static $supported_mime_types = array('image'       => array('image/jpeg', 'image/pjpeg', 'image/jpg', 'image/gif', 'image/png'),
                                               'css'         => array('text/css', 'text/plain', 'text/plain; charset=us-ascii', 'text/x-c'),
                                               'text'        => array('text/plain'),
                                               'ajax'        => array('application/json', 'text/xml', 'text/plain', 'application/rss+xml', 'application/rss+xml; charset=ISO-8859-1'),
                                               'feed'        => array('text/xml', 'application/rss+xml', 'application/rss+xml; charset=ISO-8859-1'),
                                               'pdf'         => array('application/pdf'),
                                               'flash'       => array('application/x-shockwave-flash'),
                                               'application' => array('application/x-shockwave-flash', 'application/pdf'),
                                               'media'       => array('audio/mpeg', 'audio/x-mpeg', 'audio/mp3', 'audio/x-mp3', 'audio/mpeg3', 'audio/x-mpeg3', 'audio/mpg', 'audio/x-mpg', 'audio/x-mpegaudio')
                                               );

  /**
   * @static
   * @access public
   *
   * Returns a multidimensional array of all supported mimetypes.
   *
   * @return mixed self::$supported_mime_types
   */
  static public function getAll() {
    return self::$supported_mime_types;
  }

  /**
   * @static
   * @access public
   *
   * Gets all of the available file type extensions (key) and their mimetypes (value) that are supported.
   *
   * @return type
   */
  static public function getFileTypeExtensions() {
    return self::$file_type_extensions;
  }

  /**
   * @static
   * @access public
   *
   * Checks the file to verify it is a valid extension supported on our file system.
   *
   * @param  string  $file_type
   * @param  string  $ext
   * @return boolean  Returns true if is valid, false otherwise.
   */
  static public function isValidFileTypeExt($file_type, $ext) {
    return (isset(self::$supported_mime_types[$file_type]) && in_array($ext, self::$file_type_extensions[$file_type]) ?: false);
  }

  /**
   * @static
   * @access public
   *
   * Checks if the mimetype passed in is supported.
   *
   * @param  string   $mimetype
   * @return boolean  True if it is supported, false otherwise.
   */
  static public function isSupported($mimetype) {
    $check_mimetype = self::getFileTypes($mimetype);
    return (!empty($check_mimetype) ?: false);
  }

  /**
   * @static
   * @access public
   *
   * Gets the mimetype for a given file.
   * (Optional) get the mimetype based on what the filesystem finds.  By default, uses php's finfo
   *
   * @param  string     $filename            The file to check a mimetype information for.
   * @param  boolean    $use_filesystem      When true, obtains the file's mimetype information via shell_exec.
   * @return string     Returns the mimetype for the requested file, or NULL if there was an error.
   */
  static public function getForFile($filename, $use_filesystem = false) {
    if(false == Services::isValidFilepath($filename, true)) {
      throw new Exception("filename ($filename)", Exception::FILE_NOT_FOUND);
    }

    $file_mimetype = null;

    if($use_filesystem !== false) {
      $system_info = shell_exec("file --mime $filename");
      if(!is_null($system_info)) {
        $system_command_results = explode(":", $system_info);
        if(trim($system_command_results[1]) != "ERROR" && !isset($system_command_results[2])) {
          $file_mimetype = trim($system_command_results[1]);
        } else {
          throw new Exception("filename ($filename)", Exception::FILESYSTEM_MIMETYPE_CHECK_ERROR);
        }
      } else {
        throw new Exception("filename ($filename) (Filesystem command was unable to be executed!)", Exception::FILESYSTEM_MIMETYPE_CHECK_ERROR);
      }
    } else {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $file_mimetype = finfo_file($finfo, $filename);
      finfo_close($finfo);
      if(false === $file_mimetype) {
        throw new Exception("filename ($filename)", Exception::UNKNOWN_MIMETYPE);
      }
    }
    return $file_mimetype;
  }

  /**
   * @static
   * @access public
   *
   * Returns a list of mimetypes supported by the passed in file_type.
   *
   * @param  string  $file_type
   * @return mixed   Returns an array of supported mimetypes found for the shortname.  A blank array is returned if
   *                 the shortname isn't found.
   *
   * @see self::file_type_extensions for information on file_types
   */
  static public function getAllByFileType($file_type) {
    return (true === self::isFileType($file_type) ? self::$supported_mime_types[strtolower($file_type)] : array());
  }

  /**
   * @static
   * @access public
   *
   * Returns all file_types for the mimetype being requested.
   *
   * @param  type   $mimetype
   * @return mixed  Returns an array of all $file_types found for the mimetype.  Returns a blank array if nothing was found.
   *
   * @see self::file_type_extensions for information on file_types
   */
  static public function getFileTypes($mimetype) {
    $results = array();
    foreach(self::$supported_mime_types as $file_type => $supported_list) {
      if(in_array($mimetype, $supported_list)) {
        $results[] = $file_type;
      }
    }
    return $results;
  }

  /**
   * @static
   * @access public
   *
   * Checks to see if the mimetype is valid for the $file_type.
   *
   * @param  string   $mimetype
   * @param  string   $file_type
   * @return boolean  Returns true if the mimetype is supported, false if not.
   *
   * @see self::file_type_extensions for information on file_types
   */
  static function inFileType($mimetype, $file_type) {
    return (self::isValidShortname($file_type) && is_string($mimetype)
            && in_array(strtolower($mimetype), self::$supported_mime_types[$file_type]) ?: false);
  }


  /**
   * @static
   * @access public
   *
   * Checks if the value passed in is a valid shortname.
   *
   * @param  string   $file_type
   * @return boolean  Returns true if valid, false otherwise.
   *
   * @see self::file_type_extensions for information on file_types
   */
  static public function isFileType($file_type) {
    return (is_string($file_type) && isset(self::$supported_mime_types[strtolower($file_type)]) ?: false);
  }

  /**
   * @static
   * @access public
   * @uses \MyProject\Utils\Sanitizer
   *
   * Gets the mimetypes that correspond to the extension passed in.
   *
   * @param  string $ext
   * @return mixed  Returns an array of mimetypes supported by the extension.  Returns NULL if the extension isn't
   *                a valid string or not found.
   */
  static public function getByExtension($ext) {
    if(!is_string($ext)) {
      return null;
    }

    $ext = strtolower(\MyProject\Utils\Sanitizer::sanitizeANHU(str_replace('.', $ext)));
    return (isset(self::$extensions[$ext]) ? self::$extensions[$ext] : null);
  }
}
?>