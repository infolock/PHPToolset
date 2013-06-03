<?php
/**
 * @author Jonathon Hibbard
 * Sanitize Stuff...
 */
namespace MyProject\Utils;
class Sanitizer {
  const PATTERN_SAFE_CHARS                = '/&(#)?[a-zA-Z0-9]{0,};/';
  const PATTERN_SAFE_CHARS_WITH_SPACES    = '/&(#)?[a-zA-Z0-9]{0,};\s/';

  const PATTERN_ALPHA                     = '/[^a-zA-Z]/';
  const PATTERN_ALPHA_WITH_SPACES         = '/[^a-zA-Z\s]/';

  const PATTERN_ALPHA_NUMERIC             = '/[^a-zA-Z0-9]/';
  const PATTERN_ALPHA_NUMERIC_WITH_SPACES = '/[^a-zA-Z0-9\s]/';

  const PATTERN_ANH                       = '/[^A-Za-z0-9\-]*/';
  const PATTERN_ANH_WITH_SPACES           = '/[^A-Za-z0-9\-\s]*/';

  const PATTERN_ANHU                      = '/[^-a-zA-Z0-9_]/';
  const PATTERN_ANHU_WITH_SPACES          = '/[^-a-zA-Z0-9_\s]/';

  const PATTERN_ANHUSDS                   = '/[^A-Za-z0-9-_",\']/';
  const PATTERN_ANHUSDS_WITH_SPACES       = '/[^A-Za-z0-9-_",\'\s]/';

  const PATTERN_RESOURCE                  = '/[^-a-zA-Z,]/';
  const PATTERN_RESOURCE_WITH_SPACES      = '/[^-a-zA-Z,\s]/';

  const REPEATING_UNDERSCORES             = '/_{2,}/';

  const PATTERN_SAFE_FILENAME             = '/[^a-z0-9\\/\\\\_.:-]/i';

  const PATTERN_PHP_DATE_FORMAT           = '/[^A-Za-z0-9\-:]/';
  /**
   * Pattern ONLY matches dates in YYYY-DD-MM format.
   */
  const PATTERN_PHP_YYYMMDD_DATE_FORMAT   = '^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$';

  /**
   * @author nessthehero at gmail dot com (http://us2.php.net/manual/en/function.htmlspecialchars.php#97991)
   *
   * @param  mixed $string
   * @param  const $flags ENT_QUOTES (default) | ENT_COMPAT | ENT_NOQUOTES
   * @param  string $charset
   * @param  boolean $double_encode (default true)
   * @return mixed $out
   *
   * @author Jonathon Hibbard
   * Renamed first param ($var) to $content
   * Added options for different ENT types and Encoding Types.
   * Added html_entity_decode to the $var if string to ensure they aren't doing anything sneaky...
   * Renamed from formspecialchars to deepSanitizeSpecialChars
   */
  public static function deepSanitizeSpecialChars($content, $flags = ENT_QUOTES, $charset = 'UTF-8', $double_encode = true) {
    if(!in_array($charset, array("UTF-8", "ISO-8859-1", "ISO-8859-15", "cp866", "cp1251", "cp1252", "KOI8-R", "BIG5", "GB2312", "BIG5-HKSCS", "Shift_JIS", "EUC-JP"))) {
      $charset = "UTF-8";
    }
    $pattern = '/&(#)?[a-zA-Z0-9]{0,};/';
    $double_encode = (bool)$double_encode;
    if(is_array($content)) {
      $out = array();
      foreach($content as $key => $v) {
        $out[$key] = self::deepSanitizeSpecialChars($v);
      }
    } else {
      $trans = get_html_translation_table(HTML_ENTITIES);
      $encoded = strtr($content, $trans);

      $search_and_replace = array(
        chr(145) => "'", // left single quote
        chr(146) => "'", // right single quote
        chr(147) => '"', // left double quote
        chr(148) => '"', // right double quote
        chr(151) => '-'  // emdash
      );

      $out = str_replace(array_keys($search_and_replace), $search_and_replace, $encoded);
      $out = html_entity_decode($out); // Make sure they aren't trying to be sneaky...
      while(preg_match($pattern,$out) > 0) {
        $out = htmlspecialchars_decode($out, $flags);
      }

      $out = htmlspecialchars(stripslashes(trim($out)), $flags, $charset, $double_encode);
    }
    return $out;
  }

  public static function sanitizePattern($str, $pattern='-a-zA-Z0-9_+&') {
    $pattern = str_replace('+', '\\+', $pattern);
    if(is_array($str)) {
      $out = array();
      foreach($str as $key => $v) {
        $out[$key] = self::sanitizePattern($v, $pattern);
      }
    } else {
      $out = preg_replace('/[^' . $pattern . ']/', '', $str);
    }
    return $out;
  }

  /**
   * @author Jonathon Hibbard
   * ANHU = Alpha, Numeric, Hyphen, Underscore
   *
   * Removes any character that not alpha, numeric, hyphen ( - ), or underscore ( _ ) from a string.
   * If $string is an array, then it will recursively loop through all keys and apply this method onto the values.
   *
   * @param  mixed $str  // The String to be ANHU sanitized..
   * @return mixed $out
   */
  public static function sanitizeANHU($str) {
    if(is_array($str)) {
      $out = array();
      foreach($str as $key => $v) {
        $out[$key] = self::sanitizeANHU($v, $keep_underscore);
      }
    } else {
      $out = preg_replace('/[^-a-zA-Z0-9_]/', '', $str);
    }
    return $out;
  }

  public static function sanitizeANHUS($str, $keep_apostrophes=false) {
    if(is_array($str)) {
      $out = array();
      foreach($str as $key => $v) {
        $out[$key] = self::sanitizeANHUS($v, $keep_apostrophes);
      }
    } else {
      $regex = ($keep_apostrophes ? "/[^-a-zA-Z0-9_' ]/" : "/[^-a-zA-Z0-9_ ]/");
      $out = preg_replace($regex, '', $str);
    }
    return $out;
  }

  public static function sanitizeURL($str) {
    if (is_array($str)) {
      $out = array();
      foreach($str as $key => $val) {
        $out[$key] = self::sanitizeURL($val);
      }
    } else {
      $trans = array(
        "\"" => "",
        "'"  => "",
        "&"  => "+",
        "?"  => "",
        " "  => "_",
        "/"  => "-",
        ":"  => "-",
        '%'  => ''
        );
      $out = strtr($str, $trans);
    }
    return($out);
  }

  /**
   * Get only the alpha chars back...
   * @param  string $str
   * @return mixed // String if not an array.  If an array, values will be string.
   */
  public static function sanitizeAlphaOnly($str, $with_spaces = false) {
    if(is_array($str)) {
      $out = array();
      foreach($str as $key => $v) {
        $out[$key] = self::sanitizeAlphaOnly($v);
      }
    } else {
      $out = preg_replace(($with_spaces === false ? self::PATTERN_ALPHA : self::PATTERN_ALPHA_WITH_SPACES), '', $str);
    }
    return $out;
  }
  /**
   * Takes in a string and returns it as a valid structure for a City and/or State
   * If $string is an array, then it will recursively loop through all keys and apply this method onto the values.
   *
   * @param  mixed $str
   * @return mixed $out
   */
  public static function sanitizeResourceLabel($str) {
    if(is_array($str)) {
      $out = array();
      foreach($str as $key => $v) {
        $out[$key] = self::sanitizeANHU($v);
      }
    } else {
      $out = preg_replace('/[^-a-zA-Z,]/', '', $str);
    }
    return $out;
  }

  public static function sanitizeTitle($str, $length = null) {
    $length = (isset($length) ? intval($length) : strlen($str));
    return substr(preg_replace(self::PATTERN_SAFE_CHARS_WITH_SPACES, '', $str), 0, $length);
  }

  /**
   * Removes all HTML Special Characters, HTML Entities, and Tags from a trimmed string.
   * If $string is an array, then it will recursively loop through all keys and apply this method onto the values.
   *
   * @param  mixed   $string       // The String to be stripped
   * @param  boolean $removeQuotes // If true, removes quotes from the string.
   * @return mixed   $out
   */
  public static function removeAllEntities($string, $removeQuotes = false) {
    if(is_array($string)) {
      $out = array();
      foreach($string as $key => $v) {
        $out[$key] = self::sanitizeANHU($v);
      }
    } else {
      $out = trim(strip_tags(htmlspecialchars_decode(html_entity_decode($string)), ($removeQuotes === true ? "\x22\x27" : NULL)));
    }
    return $out;
  }

  /**
   * @author Jonathon Hibbard
   * Takes any string and returns a name suitable for a directory/file.
   * If $string is an array, then it will recursively loop through all keys and apply this method onto the values.
   *
   * @param  mixed $string
   * @return mixed $out
   */
  public static function sanitizeFilepath($string) {
    if(is_array($string)) {
      $out = array();
      foreach($string as $key => $v) {
        $out[$key] = self::sanitizeANHU($v);
      }
    } else {
      $new_string = trim($string);
      $exclude_exp = "/[^A-Za-z0-9_\/.\-\s]*/";
      $new_string = str_replace(array(" ", "-"), "_", preg_replace($exclude_exp, "", $new_string));

      $multiple_exp = "/_{2,}/";
      $out = preg_replace($multiple_exp, "_", $new_string);
    }
    return $out;
  }

  /**
   * @author Jonathon Hibbard
   * Takes any string and returns a name suitable for a directory/file.
   * If $string is an array, then it will recursively loop through all keys and apply this method onto the values.
   *
   * @param  mixed $string
   * @return mixed $out
   */
  public static function sanitizeFileName($string) {
    if(is_array($string)) {
      $out = array();
      foreach($string as $key => $v) {
        $out[$key] = self::sanitizeANHU($v);
      }
    } else {
      $new_string = trim($string);
      $exclude_exp = "/[^A-Za-z0-9\-\s]*/";
      $new_string = strtoupper(str_replace(array(" ", "-"), "_", preg_replace($exclude_exp, "", $new_string)));

      $multiple_exp = "/_{2,}/";
      $out = preg_replace($multiple_exp, "_", $new_string);
    }
    return $out;
  }

  /**
   *  This block ensures that the user is not defining a relative path and instead is defining a domain with http.
   * if http:// isn't present, it is appended to ensure proper URL structure.
   */
  public static function getProperURL($url) {
    $matches  = array();
    $matches2 = array();
    preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);
    $host     = $matches[1];
    preg_match('/[^.]+\.[^.]+$/', $host, $matches2);
    if(isset($matches2[0]) && !empty($matches2[0]) && !preg_match("~^(?:f|ht)tps?://~i", $matches2[0])) {
      $url = "http://" . $matches2[0];
    }
    return $url;
  }

  public static function normaliseApostrophes($string) {
    $out = strtr($string, array("`" => "'",
                                "´" => "'",
                                "¹" => "'"));
    return ($out);
  }

  /**
   * Used to translate certain characters from MS Word into ASCII equivalent
   *
   * @param  string $text test to sanitize
   * @see http://www.danielkassner.com/2010/05/27/sanitize-copypaste-text-from-word
   * @return string - sanitized string
   */
  public static function sanitizeFromWord($text = '') {

    $chars = array(
      130=>',',     // baseline single quote
      131=>'NLG',   // florin
      132=>'"',     // baseline double quote
      133=>'...',   // ellipsis
      134=>'**',    // dagger (a second footnote)
      135=>'***',   // double dagger (a third footnote)
      136=>'^',     // accent
      137=>'o/oo',  // permile
      138=>'Sh',    // S Hacek
      139=>'<',   // left single guillemet
      140=>'OE',    // OE ligature
      145=>'\'',    // left single quote
      146=>'\'',    // right single quote
      147=>'"',   // left double quote
      148=>'"',   // right double quote
      149=>'-',   // bullet
      150=>'-',   // endash
      151=>'--',    // emdash
      152=>'~',   // tilde accent
      153=>'(TM)',  // trademark ligature
      154=>'sh',    // s Hacek
      155=>'>',   // right single guillemet
      156=>'oe',    // oe ligature
      159=>'Y',   // Y Dieresis
      169=>'(C)',   // Copyright
      174=>'(R)'    // Registered Trademark
    );

    foreach ($chars as $chr=>$replace) {
      $text = str_replace(chr($chr), $replace, $text);
    }
    return $text;
  }
}
?>
