<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Handle session data
 * @author sahib
 */
class QuarkSess
{
  /**
   * Project session name
   * @var string
   */
  private $_session_name;
  
  /**
   * Cookie life time in seconds.
   * @see QuarkSess::setSaveCookie()
   * @var int
   */
  private $_cookie_life_time;

  /**
   * Namespace to use.
   * 
   * @see QuarkSess::useNamespace()
   * @var string
   */
  private static $_namespace = 'default';
  
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->_session_name     = Quark::getConfigVal('session_name');
    $this->_cookie_life_time = Quark::getConfigVal('cookie_life_time');
    
    if (!isset($_SESSION)) {
      session_start();
    }
    
    // Create or load the session data.
    if (!isset($_SESSION[$this->_session_name][self::$_namespace])) {
      
      $cookie_name = $this->_session_name.'_'.self::$_namespace;
      if( isset($_COOKIE[$cookie_name]) ){
        // Load session data from cookie
        $_SESSION[$this->_session_name][self::$_namespace] = unserialize(
          base64_decode($_COOKIE[$cookie_name])
        );
      } else {
        // Create new session data
        $this->_createSessionData(self::$_namespace);
      }
    }
    
    // Try to send cookie to client.
    $this->_sendCookie(self::$_namespace);
  }
  
  /**
   * Creates new session data using the namespace $namespace
   * @param string $namespace Namespace
   */
  public function _createSessionData($namespace)
  {
    $_SESSION[$this->_session_name][$namespace] = array(
      '_quark_access_level_' => 0,
      '_quark_save_cookie_' => false
    );
  }
  
  /**
   * Set the namespace to work.
   * @param string $namepsace Name space
   */
  public static function useNamespace($namespace)
  {
    self::$_namespace = $namespace;
  }

  /**
   * Send cookie to client with session data from the namespace in use or the
   * namespace defined with $namespace.
   * The cookie will be sent in the next page request.
   * Return object reference for method chaining.
   * 
   * @param string $namespace
   * @return QuarkSess
   */
  public function saveCookie($namespace = null)
  {
    if($namespace == null){
      $namespace = self::$_namespace;
    }

    $_SESSION[$this->_session_name][$namespace]['_quark_save_cookie_'] = true;
    $this->_sendCookie($namespace);
    return $this;
  }
  
  /**
   * Send cookie with the session data defined in the namespace defined by $namespace
   * @see QuarkSess::saveCookie()
   */
  private function _sendCookie($namespace)
  {
    // Send cookie only if was specified by method saveCookie() in the namespace
    // defined by $namespace
    if ($_SESSION[$this->_session_name][$namespace]['_quark_save_cookie_']) {
      setcookie($this->_session_name.'_'.$namespace
        , base64_encode(serialize($_SESSION[$this->_session_name][$namespace]))
        , (time() + $this->_cookie_life_time)
        , QUARK_APP_DIR
        , Quark::getConfigVal('cookie_domain')
      );
    }
  }
  
  /**
   * Define the session access level for the namespace in use.
   * Return the object reference for method chaining.
   * 
   * @see QuarkSess::getAccessLevel()
   * @see Quark::bigBang()
   * @see QuarkController::setDefaultAccessLevel()
   * @see QuarkController::setActionsAccessLevel()
   * @param int $access_level
   * @return QuarkSess
   */
  public function setAccessLevel($access_level)
  {
    $_SESSION[$this->_session_name][self::$_namespace]['_quark_access_level_']
      = $access_level;
      
    return $this;
  }
  
  /**
   * Return the session access level for the namespace in use.
   * 
   * @see QuarkSess::setAccessLevel()
   * @return int
   */
  public function getAccessLevel()
  {
    return $_SESSION[$this->_session_name][self::$_namespace]
      ['_quark_access_level_'];
  }
  
  /**
   * Save the value $value in session namespace in use (or the
   * namespace $namespace if defined) with the key $key.
   * 
   * Return the object reference for method chaining.
   * 
   * @see QuarkSess::get()
   * @param string $key
   * @param mixed $value
   * @param string $namespace
   * @return QuarkSess
   */
  public function set($key, $value, $namespace = null)
  {
    if($namespace == null){
      $namespace = self::$_namespace;
    }
    $_SESSION[$this->_session_name][$namespace][$key] = $value;
    return $this;
  }
  
  /**
   * Return the value of $key defined with set() from the namespace in use or the
   * namespace $namespace if defined.
   * 
   * @see QuarkSess::set()
   * @param string $key
   * @param string $namespace
   * @return mixed
   */
  public function get($key, $namespace = null)
  {
    if($namespace == null){
      $namespace = self::$_namespace;
    }

    if (isset($_SESSION[$this->_session_name][$namespace][$key])) {
        return $_SESSION[$this->_session_name][$namespace][$key];
    } else {
        return null;
    }
  }
  
  /**
   * Delete the session data from namespace in use or
   * namespace $namespace if defined.
   */
  public function kill($namespace = null)
  {
    if($namespace == null){
      $namespace = self::$_namespace;
    }
    
    unset($_SESSION[$this->_session_name][self::$_namespace]);
    setcookie($this->_session_name. '_'. $namespace
      , ''
      , (time() - 1)
      , QUARK_APP_DIR
      , Quark::getConfigVal('cookie_domain'));
    
    // Create new session data
    $this->_createSessionData($namespace);
  }

  /**
   * Return the namespace in use.
   * @return string
   */
  public function getNamespace()
  {
    return self::$_namespace;
  }
}
