<?php
/**
 * @author   Jonathon Hibbard
 * @desc     A File Upload class.
 *
 * @throws MyProject\FileManager\Exception
 *
 * @uses
 * Services  - Services.php
 * Mimetype  - Mimetype.php
 * Exception - Exception.php
 *
 * @example
 * $uploadManager = new \MyProject\FileManager\Upload();
 * # This is the default and is not needed, but showing the ability to do so if needed..
 * $uploadManager->setTempPath('/path/to/tmp/folder/);
 */

namespace MyProject\FileManager;

class Upload {
  # Reusable Error messages.
  const SET_MAX_FAILED        = 'Cannot set a max_bytes higher than the server\'s upload_max_filesize!';
  const INVALID_SET_MAX_SIZE  = 'Invalid Filesize supplied!';
  const EMPTY_QUEUE           = 'FAILED processing an empty queue in File Manager processQueue!';
  const DUPLICATE_QUEUE_ITEM  = 'ERROR: Duplicate Items are NOT allowed in the queue!';
  const FILE_INFO_ERROR       = 'FAILED obtaining file information in FileManager Upload processQueue!';
  const FILE_SOURCE_ERROR     = 'ERROR obtaining the uploaded file\'s source!';
  const FILE_MIMETYPE_ERROR   = 'Invalid Mimetype Supplied to the FileManager Upload processQueue!';

  # Defines the default file path for where files will be placed.
  const DEFAULT_TEMP_PATH     = '/tmp';
  # Defines the maximum number of backups that can be used for a file that already exists.
  const MAX_BACKUPS           = 50;

  /**
   * These constants should be used to ensure that we have the correct "file_type" name being used
   * when dealing with the Mimetype Object.  This ensures that the correct key will be used when
   * evaluating the file's mimetype.
   */
  const IMAGE_FILE_TYPE       = 'image';
  const APPLICATION_FILE_TYPE = 'application';
  const CSS_FILE_TYPE         = 'css';
  const TEXT_FILE_TYPE        = 'text';
  const AJAX_FILE_TYPE        = 'ajax';
  const FEED_FILE_TYPE        = 'feed';
  const MEDIA_FILE_TYPE       = 'media';

  /**
   * @access protected
   *
   * Default max_filesize for a file is 1MB.  This variable, though, stores all representations in raw bytes.
   * This variable is used to ensure a file being uploaded does not exceed this variable.
   */
  protected $max_bytes          = 1048576;

  /**
   * @access protected
   * Defines the temporary directory location file uploads will be moved to for sanitization and validation.
   */
  protected $tempUploadPath;

  /**
   * @access protected
   *
   * Array of mimetypes files must have to be properlly processed from the queue.
   * @see Mimetype
   * @see $file_queue
   * @see self::processQueue()
   */
  protected $validMimeTypes = array();

  /**
   * @access protected
   *
   * Array of files that are to be uploaded that have passed the validMimeType check.
   * The key of this array is the NEW FILENAME that will be used for the ORIGINAL (Uploaded) FILENAME being processed.
   * We make the $new_filename the key here as, well, do we really want to overwrite a "new" file immediately with
   * a different uploaded file?  I think not.
   */
  protected $file_queue     = array();

  /**
   * @access protected
   *
   * Boolean value that, when set to true, will first delete an existing file and overwrite it with the file
   * being uploaded.
   */
  protected $overwrite_existing_files = false;

  /**
   * @access protected
   *
   * Sets what type of media the file must be in order to pass the initial validation.
   * @param type $file_type
   */
  protected function setMimeTypes($file_type = self::IMAGE_FILE_TYPE, $overwrite_existing_files = false) {
    if(!isset($file_type) || empty($file_type) || !is_string($file_type)) {
      $file_type = 'image';
    }

    # force boolean
    $this->overwrite_existing_files = (bool)intval($overwrite_existing_files);

    $file_type = strtolower($file_type);

    $this->validMimeTypes = Mimetype::getAllByFileType($file_type);
    if(empty($this->validMimeTypes)) {
      throw new Exception("file_type ($file_type)", Exception::UNKNOWN_FILE_TYPE);
    }
  }


  /**
   * @access protected
   *
   * Processes the file to verify it meets our standards for being allowed into the queue.
   *
   * @param  string   $original_filename
   * @param  string   $new_filename
   * @param  boolean  $overwrite_if_exists
   * @param  boolean  $return_filesource
   * @throws MyProject\FileManager\Exception
   */
  protected function processFileForQueue($original_filename, $new_filename, $overwrite_if_exists, $return_filesource) {
    # Do we still have a file to work with?
    if(false === $new_filename) {
      if($overwrite_if_exists === false) {
        # Duplicate File!
        throw new Exception("new filename ($new_filename)", Exception::DUPLICATE_FILE);
      } else {
        # Permission issue!
        throw new Exception("new filename ($new_filename)", Exception::FILESYSTEM_PERMISSION_ERROR);
      }
    }

    # Make sure the file is not already in our queue as we cannot have duplicate keys...
    if(isset($this->file_queue[$new_filename])) {
      throw new Exception(self::DUPLICATE_QUEUE_ITEM . "new_filename ($new_filename)", Exception::DUPLICATE_FILE);
    }

    $this->file_queue[$new_filename] = array("original_filename" => $original_filename,
                                             "return_filesource" => $return_filesource,
                                            );
  }

  /**
   * @access public
   *
   * The main constructor.  The file_type passed in defines what "type" of files are allowed for this
   * upload instance.  Only those type of files will be allowed into the queue for upload.
   * @see Mimetype
   *
   * @param  string $file_type
   * @throws Exception
   * @uses
   * Mimetype - Mimetype.php
   */
  public function __construct($file_type = 'image') {
    if(false === Mimetype::isFileType($file_type)) {
      throw new Exception('file_type(' . $file_type . ')', Exception::UNSUPPORTED_FILE_TYPE);
    }

    $this->setTempUploadPath();
    $this->setMimeTypes($file_type);
  }

  /**
   * @access public
   *
   * Sets the temporary fileupload path to use for file uploads.
   *
   * @param boolean|string $path  - When left at false (default), uses the /tmp folder.  Otherwise, uses what is passed in.
   * @throws MyProject\FileManager\Exception
   */
  public function setTempUploadPath($path = false) {
    if(false === $path) {
      $path = self::DEFAULT_TMP_PATH;
    }

    # Sanitize the filepath and verify it is writable/exists.
    $path = Services::isValidFilepath($path, true);
    if(false === $path) {
      throw new Exception("Invalid Path supplied to setTempUploadPath!", Exception::FILE_NOT_FOUND);
    }

    $this->tempUploadPath = $path;
  }

  /**
   * @access public
   *
   * Returns the maxium filesize for the current instance.
   *
   * @return integer $max_filesize
   */
  public function getMaxFilesize() {
    return $this->max_filesize;
  }

  /**
   * @access public
   *
   * Sets the max filesize in raw bytes.  Only an integer-based raw byte size value is supported at this time.
   * IE: to specify 1MB, it would be passed in as 1048576.
   *
   * If a non-integer is passed, an exception will be thrown.
   *
   * NOTE: The value passed into this method will be used to ensure that a given file does not exceed
   * both the UPLOAD_MAX_FILESIZE *AND* the POST_MAX_SIZE set on the server.
   *
   *
   * @see Services::getServerMaxSizeSettings for more information.
   *
   * @param integer $size  The size in BYTES to set the maximum filesize to.  0 (default) uses the server's max filesize.
   * @throws Exception
   */
  public function setMaxFilesize($size = 0) {
    $size = intval($size);
    if($size < 0) {
      throw new Exception(self::INVALID_SET_MAX_SIZE . " size($size) and ServerSize = " . Services::getServerMaxByteSize(), Exception::INVALID_BYTE_VALUE);
    } elseif($size > 0) {
      if($size > Services::getServerMaxByteSize()) {
        throw new Exception(self::SET_MAX_FAILED . " size($size) and ServerSize = " . Services::getServerMaxByteSize(), Exception::SERVER_MAX_BTYES_EXCEEDED);
      }
      $this->max_bytes = $size;
    } else {
      $this->max_bytes = Services::getServerMaxByteSize();
    }
  }

  /**
   * @access public
   *
   * Adds a file to the queue that is to be uploaded.
   *
   * @param  string    $original_filename
   * @param  string    $new_filename
   * @param  boolean   $overwrite_if_exists   Default by false.  When true, removes the destination file for the new one.
   * @param  boolean   $return_filesource     When false (default), just returns the filename and information.  When true,
   *                                          will also return the file's source/contents.
   * @throws MyProject\FileManager\Exception
   *
   * @see $file_queue in the properties section of this class.
   */
  public function addToQueue($original_filename, $new_filename, $overwrite_if_exists = false, $return_filesource = false) {
    # Sanitize the original filename and ensure that it exists.
    $original_filename = Services::isValidFilepath($original_filename, true);
    # Do we still have a file to work with?
    if(false === $original_filename) {
      throw new Exception("original_name ($original_filename)", Exception::FILE_NOT_FOUND);
    }
    # Sanitize the original filename, and let the overwrite_existing_files definition determine if we care if it exists.
    $new_filename = Services::isValidFilepath($this->tempUploadPath . '/' . basename($new_filename));

    $this->processFileForQueue($original_filename, $new_filename, $overwrite_if_exists, $return_filesource);
  }

  /**
   * @access public
   *
   * Returns the current files (if any) in the file_queue waiting to be uploaded.
   *
   * @return mixed $file_queue
   */
  public function getQueue() {
    return $this->file_queue;
  }

  /**
   * @access public
   *
   * For whatever reason, allows one to remove a file from the file upload queue if it exists.
   *
   * @param string $filename
   */
  public function removeFromQueue($filename) {
    if(isset($this->file_queue[$filename])) {
      unset($this->file_queue[$filename]);
    }
  }

  /**
   * @access public
   *
   * Empties the file queue.
   */
  public function resetQueue() {
    $this->file_queue = array();
  }

  /**
   * @access public
   *
   * Processes and performs the upload process for any files in the queue.
   * If no files are in the queue, an exception will be thrown.
   *
   * @param  boolean $delete_original  - Do we delete the original file after being processed successfully?
   * @return mixed   $results   - Multidimensional array containing all files (and their information) that were processed.
   * @throws MyProject\FileManager\Exception
   *
   * @example
   * The $results array will have the following structure:
   * $results = array(0 => array("dirname"             => string,
   *                             "basename"            => string,
   *                             "extension"           => string,
   *                             "filename"            => string,
   *                             "mimetype"            => string,
   *                             "filesystem_mimetype" => string,
   *                             "errors"              => array(),
   *                             "warnings"            => array(),
   *                             "original_filename"   => string,
   *                            )
   *                 );
   */
  public function processQueue($delete_original = true) {
    if(empty($this->file_queue)) {
      throw new Exception(self::EMPTY_QUEUE);
    }

    $results = array();
    foreach($this->file_queue as $new_filename => $settings) {
      $is_uploaded = true;
      $file_info = array_merge(array('file_source'       => null,
                                     'original_filename' => $settings['original_filename']
                                    ), Services::getFileInfo($settings['original_filename']));

      # So long as we have no errors we can continue.  Otherwise, we skip this file.
      if(empty($file_info['errors'])) {
        # we have a valid mimetype right?
        if(!in_array($file_info['mimetype'], $this->validMimeTypes)) {
          $file_info['errors'][] = self::FILE_MIMETYPE_ERROR;
        } else {
          # Copy the file.
          try {
            Services::copyFile($settings['original_filename'], $new_filename, $delete_original, $this->overwrite_existing_files);
          } catch(Exception $e) {
            $file_info['errors'][] = $e->getMessage();
            $is_uploaded = false;
          }
          # Should we also return the file source?
          if($is_uploaded === true && $settings['return_filesource'] === true) {
            try {
              $file_info['file_source'] = Services::getSource($new_filename);
            } catch(Exception $e) {
              $file_info['errors'][] = self::FILE_SOURCE_ERROR . "Report was: " . $e->getMessage();
            }
          }
        }
      } else {
        # We have errors.  Let's report then that the upload for this file failed too then.
        $file_info['errors'][] = self::FILE_INFO_ERROR;
      }


      $results[] = $file_info;
      unset($this->file_queue[$new_filename]);
    }

    $this->resetQueue();

    return $results;
  }
}
?>