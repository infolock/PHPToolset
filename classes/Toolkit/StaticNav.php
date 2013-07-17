<?php
/**
 * @author Jonathon Hibbard, 2013
 *
 * This is a simple object for working with a site's navigation system.
 * It is not intended to be anything less than a temporary toolkit object for instances
 * where there is a need to simply satisfy navigation functionality on the site (programaticallly).
 *
 * Built this for my new portfolio page just as a quick way to offer a temporary solution until I
 * can integrate a router/controller system.
 *
 *
 * @example
 *
 * // index.php
 * <?php
 * // Set a page (ie: 'intro') as the current page
 * include_once("/path/to/StaticNav.php");
 * StaticNav::getInstance()->setActivePage('intro');
 * 
 * include_once("topNav.php");
 * // ....
 * ?>
 *
 *
 * // topNav.php
 * <?php
 * include_once("/path/to/StaticNav.php");
 *
 * $activePageInfo = StaticNav::getInstance()->getActivePage();
 * $activePage = (!empty($activePageInfo) ? $activePageInfo['name'] : "UNKNOWN");
 *
 * // Do something with the active page name, like active an item in the nav
 * // ....
 * 
 * ?>
 */

namespace Toolkit;
class StaticNav {

  public static $instance = null;

  private $pages = array();
  private $currentPage = null;

  private function __construct() {

    $this->pages = array("intro"     => "/",
                         "portfolio" => "/",
                         "resume"    => "http://phpadvocate.com/blog/resume/",
                         "blog"      => "http://www.phpadvocate.com",
                         "github"    => "https://github.com/infolock",
                         "contact"   => "#");
  }

  public function getActivePage() {
    return ($this->currentPage != null ? $this->getPageInfoForPage($this->currentPage) : null);
  }

  public function setActivePage($page) {
    if(is_string($page) && isset($this->pages[strtolower($page)])) {
      $this->currentPage = strtolower($page);
      return true;
    }
    return false;
  }

  public function getPageInfoForPage($page) {
    if(is_string($page) && isset($this->pages[strtolower($page)])) {
      $page = strtolower($page);
      return array("name" => $page,
                   "url"  => $this->pages[$page]
                  );
    }
    return false;
  }


  public static function getInstance() {
    if(self::$instance === null) {
      self::$instance = new StaticNav();
    }
    return self::$instance;
  }

  public static function currentPage() {
    return self::getInstance()->getCurrentPage();
  }

  public static function setCurrentPage() {
    return self::getInstance()->getCurrentPage();
  }
}
?>
