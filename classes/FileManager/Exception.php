<?php
/**
 * @author Jonathon Hibbard
 *
 * FileManager Exception
 *
 * Thrown by siblings in the MyProject\FileManager namespace.
 */
namespace MyProject\FileManager;
class Exception extends \Exception {
  # Default Error
  const ERROR                           = 100;

  # Filesystem errors
  const FILESYSTEM_ERROR                = 201;
  const FILESYSTEM_STAT_ERROR           = 202;
  const FILESYSTEM_MIMETYPE_CHECK_ERROR = 203;
  const FILESYSTEM_PERMISSION_ERROR     = 204;

  # Mimetype errors
  const UNKNOWN_MIMETYPE                = 301;
  const UNKNOWN_FILE_TYPE               = 302;
  const UNSUPPORTED_MIMETYPE            = 303;
  const UNSUPPORTED_FILE_TYPE           = 304;

  # File errors
  const DUPLICATE_FILE                  = 401;
  const COPY_FILE_ERROR                 = 402;
  const RENAME_FILE_ERROR               = 403;
  const FILE_NOT_FOUND                  = 404;
  const DELETE_FILE_ERROR               = 405;
  const MOVE_FILE_ERROR                 = 406;
  const CREATE_FILE_ERROR               = 407;

  # Directory errors
  const CREATE_DIRECTORY_ERROR          = 501;
  const DELETE_DIRECTORY_ERROR          = 502;
  const RENAME_DIRECTORY_ERROR          = 503;
  const MOVE_DIRECTORY_ERROR            = 504;
  const COPY_DIRECTORY_ERROR            = 505;
  const DIRECTORY_NOT_FOUND             = 506;

  # File Size errors
  const INVALID_BYTE_VALUE              = 601;
  const INSTANCE_MAX_BYTES_EXCEEDS      = 602;
  const SERVER_MAX_BTYES_EXCEEDED       = 603;
  const SERVER_POST_BYTES_EXCEEDED      = 604;

  protected $identifier = '';

  public function __construct($message = null, $code = 0) {
    # Make sure we setup our logFilename to be the module we are dealing with.
    $this->identifier = $message;
    $this->code = $code;
    if(intval($code) > 0) {
      $message = $this->getErrorMessage();
    }

    if(null === $message) {
      $message = get_class("Called From " . $this);
    }

    parent::__construct($message, $code);
  }

  /**
   * Gets the message for a specified code
   * @return string
   */
  public function getErrorMessage() {
    switch(intval($this->code)) {
      # Filesystem Errors
      case self::FILESYSTEM_ERROR:
        return "An unknown FILESYSTEM error occurred! ({$this->identifier})";
      case self::FILESYSTEM_STAT_ERROR:
        return "FILESYSTEM STAT error occurred! ({$this->identifier})";
      case self::FILESYSTEM_MIMETYPE_CHECK_ERROR:
        return "FILESYSTEM MIMETYPE CHECK error occurred! ({$this->identifier})";
      case self::FILESYSTEM_PERMISSION_ERROR:
        return "FILESYSTEM PERMISSIONS error occurred! ({$this->identifier})";

      # Mimetype Errors
      case self::UNKNOWN_MIMETYPE:
        return "UNKNOWN MIMETYPE! ({$this->identifier})";
      case self::UNKNOWN_FILE_TYPE:
        return "UNKNOWN FILE_TYPE! ({$this->identifier})";
      case self::UNSUPPORTED_MIMETYPE:
        return "UNSUPPORTED MIMETYPE! ({$this->identifier})";
      case self::UNSUPPORTED_MIMETYPE:
        return "UNSUPPORTED FILE_TYPE! ({$this->identifier})";

      # File Errors
      case self::DUPLICATE_FILE:
        return "DUPLICATE FILE error occurred!! ({$this->identifier})";
      case self::COPY_FILE_ERROR:
        return "COPY FILE error occurred!! ({$this->identifier})";
      case self::RENAME_FILE_ERROR:
        return "RENAME FILE error occurred!! ({$this->identifier})";
      case self::FILE_NOT_FOUND:
        return "FILE NOT FOUND!! ({$this->identifier})";
      case self::DELETE_FILE_ERROR:
        return "DELETE FILE error occurred!! ({$this->identifier})";
      case self::MOVE_FILE_ERROR:
        return "MOVE FILE error occurred!! ({$this->identifier})";
      case self::CREATE_FILE_ERROR:
        return "CREATE FILE error occurred!! ({$this->identifier})";

      # Directory Errors
      case self::CREATE_DIRECTORY_ERROR:
        return "CREATE DIRECTORY error occurred!! ({$this->identifier})";
      case self::DELETE_DIRECTORY_ERROR:
        return "DELETE DIRECTORY error occurred!! ({$this->identifier})";
      case self::RENAME_DIRECTORY_ERROR:
        return "RENAME DIRECTORY error occurred!! ({$this->identifier})";
      case self::MOVE_DIRECTORY_ERROR:
        return "MOVE DIRECTORY error occurred!! ({$this->identifier})";
      case self::COPY_DIRECTORY_ERROR:
        return "COPY DIRECTORY error occurred!! ({$this->identifier})";
      case self::DIRECTORY_NOT_FOUND:
        return "DIRECTORY NOT FOUND!! ({$this->identifier})";

      # File Size Errors
      case self::INVALID_BYTE_VALUE :
        return "INVALID BYTE VALUE was supplied! ({$this->identifier})";
      case self::INSTANCE_MAX_BYTES_EXCEEDS:
        return "BYTE VALUE supplied EXCEEDS the MAX BYTES set by the current object's INSTANCE! ({$this->identifier})";
      case self::SERVER_MAX_BTYES_EXCEEDED:
        return "The FILE BYTE SIZE EXCEEDS the MAX BYTES allowed by the SERVER! ({$this->identifier})";
      case self::SERVER_POST_BYTES_EXCEEDED:
        return "The POST BYTE SIZE EXCEEDS the MAX BYTES allowed by the SERVER! ({$this->identifier})";

      # Default/Unknown Error
      case self::ERROR: default:
        return "An unknown error occurred! ({$this->identifier})";
    }
  }
}
?>