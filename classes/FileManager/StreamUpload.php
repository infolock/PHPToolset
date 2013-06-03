<?php
/**
 * @author Jonathon Hibbard
 * This script takes care of handling a file uploaded via the stream (buffer)
 *
 * @throws MyProject\FileManager\Exception, \Exception
 */

namespace MyProject\FileManager;

class StreamUpload extends Upload {
  /**
   * @static
   * @access private
   *
   * @var boolean
   *
   * We set this to true in order to prevent more than 1 file being added to the queue...
   */
  private $file_queue_full = false;

  /**
   * @static
   * @access private
   *
   * Checks the content length (file size) of the item being uploaded.
   * @throws MyProject\FileManager\Exception
   */
  private function checkContentLength() {
    if(isset($_SERVER["CONTENT_LENGTH"])){
      $content_length = (int)$_SERVER["CONTENT_LENGTH"];
      if($content_length < 1 || $content_length > Services::getServerMaxByteSize()) {
        throw new Exception("Content Length ($content_length)", Exception::SERVER_MAX_BTYES_EXCEEDED);
      } elseif($content_length > $this->max_bytes) {
        throw new Exception("Content Lenght ($content_length) exceeds the allowed maximum for file uploads!", Exception::INSTANCE_MAX_BYTES_EXCEEDS);
      }
    } else {
      throw new Exception("This stream's CONTENT_LENGTH was not found!" , Exception::INVALID_BYTE_VALUE);
    }
  }

  /**
   * @access private
   *
   * Gets the file contents from the stream.
   *
   * @return string  $temp_file
   * @throws MyProject\FileManager\Exception
   */
  private function getFileStreamContents() {
    $stream_file_source = fopen("php://input", "r");
    // create a temporary file for getting the source data.  Same thing php does for $_FILES being uploaded through form multitype
    $temp_file = tmpfile();
    // Get the
    $bytes_copied = stream_copy_to_stream($stream_file_source, $temp_file);
    fclose($stream_file_source);
    if($bytes_copied <= 0) {
      throw new Exception("FAILED to create a temporary file for the stream contents!", Exception::CREATE_FILE_ERROR);
    }
    return $temp_file;
  }

  /**
   * @static
   * @access private
   *
   * Writes the source contents that are in the stream buffer to the filesystem.
   *
   * @param  string  $source_filepath
   * @param  string  $target_filepath
   * @return string  $source_filepath  - Returns the source content handler written
   * @throws MyProject\FileManager\Exception
   */
  private static function writeStream($source_filepath, $target_filepath) {
    $target = fopen($target_filepath, "w");
    if(false === $target) {
      throw new Exception("Unable to open the targetpath file in witeStream!", Exception::FILESYSTEM_PERMISSION_ERROR);
    }
    fseek($source_filepath, 0, SEEK_SET);
    $bytes_written = stream_copy_to_stream($source_filepath, $target);
    fclose($target);
    if($bytes_written <= 0) {
      throw new Exception("Unable to write the source contents to the target location in writeStream!", Exception::FILESYSTEM_PERMISSION_ERROR);
    }

    return $source_filepath;
  }

  /**
   * @static
   * @access private
   *
   * Invoker method to process the stream and do the actual file upload from the buffer.
   *
   * @return string  Returns the fileStreamContents that were written.
   */
  private function processStream() {
    $this->checkContentLength();
    return $this->getFileStreamContents();
  }

  /**
   * @static
   * @access public
   *
   * Overriding the parent's addToQueue method to work with only stream files.
   *
   * @param  string   $original_filename
   * @param  string   $new_filename
   * @param  boolean  $overwrite_if_exists
   * @param  boolean  $return_filesource
   * @throws MyProject\FileManager\Exception
   *
   * @see Upload::addToQueue for documentation and more information.
   *
   */
  public function addToQueue($original_filename, $new_filename, $overwrite_if_exists = false, $return_filesource = false) {
    if($this->file_queue_full === true) {
      throw new Exception("The StreamUpload only supports a single file upload!  FAILED");
    }

    # Sanitize the original filename, and let the overwrite_existing_files definition determine if we care if it exists.
    $new_filename = Services::isValidFilepath($this->tempUploadPath . '/' . basename($new_filename), $overwrite_if_exists);

    $this->processFileForQueue($original_filename, $new_filename, $overwrite_if_exists, $return_filesource);

    $this->file_queue_full = true;
  }

  /**
   * @static
   * @access public
   * Overriding the parent's processQueue method to process stream data only..
   *
   * @param  boolean    $delete_newfile  // Should we delete the uplaoded file when finished working with it?
   * @return mixed      $file_info       // Returns all information related to the file that was uploaded.
   * @throws MyProject\FileManager\Exception
   */
  public function processQueue($delete_newfile = true) {
    if(empty($this->file_queue)) {
      throw new Exception(self::EMPTY_QUEUE);
    }

    $temp_file = $this->processStream();

    $results = array();
    foreach($this->file_queue as $new_filename => $settings) {
      $this->writeStream($temp_file, $new_filename);
      $tmp_info = Services::getFileInfo($new_filename, true);
      $file_info = array_merge(array('file_source'       => null,
                                     'original_filename' => $settings['original_filename']
                                    ), $tmp_info);

      # So long as we have no errors we can continue.  Otherwise, we skip this file.
      if(empty($file_info['errors'])) {
        if(!in_array($file_info['mimetype'], $this->validMimeTypes)) {
          $file_info['errors'][] = "The File was not found in the valid mimetypes!";
        } else {
          try {
            $file_info['file_source'] = Services::getSource($new_filename, true);
          } catch(Exception $e) {
            $file_info['errors'][] = self::FILE_SOURCE_ERROR . "Report was: " . $e->getMessage();
          }

          if($delete_newfile === true) {
            unlink($new_filename);
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
    $this->file_queue_full = false;

    return $results;
  }
}
?>