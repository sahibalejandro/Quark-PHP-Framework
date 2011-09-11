<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para manipular las variables de sesión asociadas
 * a la aplicación actual
 * 
 * @author sahib
 */
class QuarkSession
{
    /**
     * Nombre de la sesión para la aplicación actual
     * @var string
     */
    private $_session_name;
    
    /**
     * Tiempo de vida para la cookie, en segundos.
     * @see QuarkSession::setSaveCookie()
     * @var int
     */
    private $_cookie_life_time;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_session_name = Quark::getConfigVal('session_name');
        $this->_cookie_life_time = Quark::getConfigVal('cookie_life_time');
        
        if (!isset($_SESSION)) {
            session_start();
        }
        
        /* Inicializar la estructura de la variable de sesión */
        if (!isset($_SESSION[$this->_session_name])) {
            
            /* Crear sesión a partir de la cookie si esta existe */
            if( isset($_COOKIE[$this->_session_name]) ){
                $_SESSION[$this->_session_name] = unserialize(base64_decode($_COOKIE[$this->_session_name]));
            } else {
                /* NO hay cookie, creamos una sesión nueva */
                $_SESSION[$this->_session_name] = array(
                    '_quark_access_level_' => 0,
                    '_quark_save_cookie_' => FALSE
                );
            }
        }
        
        /* El siguiente metodo se encargará de enviar la cookie si es necesario */
        $this->_sendCookie();
    }
    
    /**
     * Indica que en la siguiente carga de la pagina se envíe la cookie
     * de la sesión al navegador.
     */
    public function saveCookie()
    {
        $_SESSION[$this->_session_name]['_quark_save_cookie_'] = TRUE;
        $this->_sendCookie();
    }
    
    /**
     * Envia la cookie al navegador si se ha especificado con QuarkSession::saveCookie()
     * 
     * @see QuarkSession::saveCookie()
     */
    private function _sendCookie()
    {
        /* Escribir cookie */
        if ($_SESSION[$this->_session_name]['_quark_save_cookie_']) {
            setcookie($this->_session_name, base64_encode(serialize($_SESSION[$this->_session_name])), (time() + $this->_cookie_life_time), '/', Quark::getConfigVal('cookie_domain'));
        }
    }
    
    /**
     * Establece el nivel de acceso para la sesión actual
     * 
     * @see QuarkSession::getAccessLevel()
     * @see Quark::bigBang()
     * @see QuarkController::setDefaultAccessLevel()
     * @see QuarkController::setActionsAccessLevel()
     * @param int $access_level
     */
    public function setAccessLevel($access_level)
    {
        $_SESSION[$this->_session_name]['_quark_access_level_'] = $access_level;
    }
    
    /**
     * Devuelve el nivel de acceso para la sesión actual, por default es 0 (cero)
     * hasta que se especifique un nuevo nivel utilizando QuarkSession::setAccessLevel()
     * 
     * @see QuarkSession::setAccessLevel()
     * @return int
     */
    public function getAccessLevel()
    {
        return $_SESSION[$this->_session_name]['_quark_access_level_'];
    }
    
    /**
     * Crea una variable nueva en la sesión actual, que estara disponible
     * a lo largo de la vida de la sesión con QuarkSession::get()
     * 
     * @see QuarkSession::get()
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $_SESSION[$this->_session_name][$key] = $value;
    }
    
    /**
     * Obtiene el valor de una variable definda con QuarkSession::set()
     * 
     * @see QuarkSession::set()
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($_SESSION[$this->_session_name][$key])) {
            return $_SESSION[$this->_session_name][$key];
        } else {
            return NULL;
        }
    }
    
    /**
     * Borra los datos de la sesión actual.
     */
    public function kill()
    {
        unset($_SESSION[$this->_session_name]);
        setcookie($this->_session_name, '', (time() - 1), '/', Quark::getConfigVal('cookie_domain'));
    }
}