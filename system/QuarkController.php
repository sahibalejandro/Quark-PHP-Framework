<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase basica para instanciar controladores
 * @author sahib
 */
class QuarkController
{
    /**
     * Instancia de QuarkView
     * 
     * @var QuarkView
     */
    protected $View;
    
    /**
     * Nivel de acceso por defecto para las acciones
     * 
     * @see QuarkController::setDefaultAccessLevel()
     * @var int
     */
    private $_default_access_level = 0;
    
    /**
     * Array asociativo con los niveles de acceso para
     * cada acción individual
     * 
     * @see QuarkController::setActionsAccessLevel()
     * @var array
     */
    private $_actions_access_level = array();
    
    /**
     * Especifica el mensaje de error para devolver en una petición AJAX
     * 
     * @see QuarkController::setAjaxError()
     * @var bool|string
     */
    private $_ajax_error = FALSE;
    
    /**
     * Especifica si no se tiene acceso a una acción vía AJAX
     * 
     * @see QuarkController::setAjaxAccessDenied()
     * @see QuarkController::quarkAccessDenied()
     * @var bool
     */
    private $_ajax_access_denied = FALSE;
    
    /**
     * Especifica si no se encontro el controlador o accion vía AJAX
     * 
     * @see QuarkController::setAjaxNotFound()
     * @see QuarkController::quarkNotFound()
     * @var bool
     */
    private $_ajax_not_found = FALSE;
    
    /**
     * Datos que serán enviados en el key 'data' de la respuesta JSON
     * 
     * @see QuarkController::setAjaxData()
     * @var mixed
     */
    private $_ajax_data = NULL;
    
    /**
     * Instancia de QuarkString
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkString
     */
    public $QuarkString;
    
    /**
     * Instancia de QuarkUrl
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkUrl
     */
    public $QuarkUrl;
    
    /**
     * Instancia de QuarkLang
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkLang
     */
    public $QuarkLang;
    
    /**
     * Instancia de QuarkSession
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkSession
     */
    public $QuarkSession;
    
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        $this->View = new QuarkView();
        $QrkStr = new QuarkString();
        
        /* Cargar e instanciar plugins, y sobrecargar View */
        foreach ( Quark::getConfigVal('auto_instances') as $instance_name ) {
            
            if (!is_array($instance_name)) {
                $arguments = array();
            } else {
                $arguments = $instance_name[1];
                $instance_name = $instance_name[0];
            }
            
            /* Incluir el archivo del plugin e instanciar el objeto en $this y $this->View */
            require_once "classes/$instance_name.php";
            
            eval('$this->$instance_name = $this->View->$instance_name = new $instance_name(' . $QrkStr->arrayToArgumentsString($arguments) . ');');
        }
    }
    
    /**
     * Establece el nivel de acceso por default para todas las acciones
     * del controlador, estos niveles pueden ser sobre-escritos por QuarkController::setActionAccessLevel()
     * 
     * @see QuarkController::setActionAccessLevel()
     * @param int $access_level
     */
    protected function setDefaultAccessLevel($access_level)
    {
        $this->_default_access_level = $access_level;
    }
    
    /**
     * Define los niveles de acceso exclusivos para cada acción, $access_levels
     * es un array asociativo 'action'=>level.
     * Las acciones que no sean definidas aqui tomaran el access level por
     * default establecido con QuarkController::setDefaultAccessLevel()
     * 
     * @see QuarkController::setDefaultAccessLevel()
     * @param array $access_levels
     */
    protected function setActionsAccessLevel($access_levels)
    {
        $this->_actions_access_level = $access_levels;
    }
    
    /**
     * Devuelve el nivel de acceso necesario para determinada acción
     * 
     * @see QuarkController::setDefaultAccessLevel()
     * @see QuarkController::setActionsAccessLevel()
     * @param string $action_name
     * @return int
     */
    public function getActionAccessLevel($action_name)
    {
        if (!isset($this->_actions_access_level[$action_name])) {
            return $this->_default_access_level;
        } else {
            return $this->_actions_access_level[$action_name];
        }
    }
    
    /**
     * Envia la respuesta JSON al navegador, con sus headers
     * necesarios
     */
    public function sendAjaxResponse()
    {
        header('content-type:application/json;charset=utf-8');
        echo json_encode(array(
            'error' => $this->_ajax_error, 
            'access_denied' => $this->_ajax_access_denied, 
            'not_found' => $this->_ajax_not_found, 
            'data' => $this->_ajax_data
        ));
    }
    
    /**
     * Establece el mensaje de error para respuesta AJAX
     * 
     * @param string $error_msg
     */
    protected function setAjaxError($error_msg)
    {
        $this->_ajax_error = $error_msg;
    }
    
    /**
     * Establece los datos a devolver en la respuesta AJAX
     * 
     * @param mixed $data
     */
    protected function setAjaxData($data)
    {
        $this->_ajax_data = $data;
    }
    
    /**
     * Establece que la sesión de usuario actual no
     * tiene permisos para la solicitud ajax invocada.
     * 
     * @see QuarkSession::setAccessLevel()
     * @see QuarkController::setDefaultAccessLevel()
     * @see QuarkController::setActionsAccessLevel()
     */
    protected function setAjaxAccessDenied()
    {
        $this->_ajax_access_denied = TRUE;
    }
    
    /**
     * Establece que el recurso de la solicitud ajax no existe.
     */
    protected function setAjaxNotFound()
    {
        $this->_ajax_not_found = TRUE;
    }
    
    /**
     * Este metodo es invocado cuando se intenta acceder a una
     * acción de nivel mayor al nivel de la sesión actual.
     * 
     * @see QuarkSession::setAccessLevel()
     * @see Quark::bigBang()
     */
    public function quarkAccessDenied()
    {
        if (QUARK_AJAX) {
            $this->setAjaxAccessDenied();
        } else {
            $this->View->render('quark-access-denied.php');
        }
    }
    
    /**
     * Este metodo es invocado cuando no se encuentra la acción
     * solicitada mediante la URL
     */
    public function quarkNotFound()
    {
        if (QUARK_AJAX) {
            $this->setAjaxNotFound();
        } else {
            $this->View->render('quark-not-found.php');
        }
    }
}