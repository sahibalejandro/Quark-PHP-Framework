<?php
/**
 * Quark 3.5 PHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
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
     * Especifica a la respuesta ajax si ocurrio un error generado por el usuario
     * 
     * @see QuarkController::setAjaxResponse()
     * @var bool
     */
    private $_ajax_error = false;
    
    /**
     * Especifica si no se tiene acceso a una acción vía AJAX
     * 
     * @see QuarkController::setAjaxAccessDenied()
     * @see QuarkController::quarkAccessDenied()
     * @var bool
     */
    private $_ajax_access_denied = false;
    
    /**
     * Especifica si no se encontro el controlador o accion vía AJAX
     * 
     * @see QuarkController::setAjaxNotFound()
     * @see QuarkController::quarkNotFound()
     * @var bool
     */
    private $_ajax_not_found = false;
    
    /**
     * Datos que serán enviados al cliente como resultado.
     * 
     * @see QuarkController::setAjaxResponse()
     * @var mixed
     */
    private $_ajax_result = null;

    /**
     * Especifica un mensaje en la respuesta ajax
     * 
     * @see QuarkController::setAjaxResponse()
     * @var string
     */
    private $_ajax_message = '';

    /**
     * Lista de rutas de archivos css para incluir con includeCssFiles()
     * 
     * @see QuarkController::appendCssFiles()
     * @var array
     */
    private $_css_files = array();

    /**
     * Lista de rutas de archivos css para incluir con includeJsFiles()
     * 
     * @see QuarkController::appendJsFiles()
     * @var array
     */
    private $_js_files = array();

    /**
     * Mapa de variables que se pasan a las vistas
     * 
     * @see QuarkController::renderView()
     * @var array
     */
    private $_view_vars = array();

    /**
     * Nombre de accion que se invoca desde Quark::bigBang()
     * 
     * @access private
     * @var string
     */
    private $_new_action_name = false;

    /**
     * Instancia de QuarkStr
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkStr
     */
    protected $QuarkStr;
    
    /**
     * Instancia de QuarkURL
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkURL
     */
    protected $QuarkURL;
    
    /**
     * Instancia de QuarkLang
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkLang
     */
    protected $QuarkLang;
    
    /**
     * Instancia de QuarkSess
     * 
     * @see QuarkController::__construct()
     * @see Quark::bigBang()
     * @var QuarkSess
     */
    public $QuarkSess;

    /**
     * Instancia de QuarkHTML
     * @var QuarkHTML
     */
    protected $QuarkHTML;
    
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
      $this->QuarkURL     = new QuarkURL();
      $this->QuarkStr     = new QuarkStr();
      $this->QuarkSess = new QuarkSess();
      $this->QuarkHTML    = new QuarkHTML();
      if(QUARK_MULTILANG){
        $this->QuarkLang = new QuarkLang();
      }

      // Metodos que siempre deben ser publicos
      $this->setActionsAccessLevel(array(
        'quarkAccessDenied' => 0,
        'quarkNotFound'     => 0,
        'quarkIncludeJs'    => 0
      ));

      // Respuesta AJAX por defecto
      if(QUARK_AJAX){
        $this->setAjaxResponse(null, '', false);
      }
    }

    /**
     * Cambia el nombre de la accion que sera invocada desde Quark::bigBang()
     * 
     * @access protected
     * @param string $new_action_name
     */
    protected function changeActionName($new_action_name)
    {
      $this->_new_action_name = $new_action_name;
    }

    public function __getNewActionName()
    {
      return $this->_new_action_name;
    }

    /**
     * Agrega una o más rutas de archivos CSS al final de la lista de archivos CSS
     * para incluir con includeCssFiles(), devuelve la referencia al controller para
     * hacer linking
     * 
     * @param string $files,...
     * @return QuarkController
     */
    protected function appendCssFiles($files)
    {
      $files = func_get_args();
      $this->_css_files = array_merge($this->_css_files, $files);
      return $this;
    }

    /**
     * Agrega una o más rutas de archivos CSS al inicio de la lista de archivos CSS
     * para incluir con includeCssFiles(), devuelve la referencia al controller para
     * hacer linking
     * 
     * @param string $files,...
     * @return QuarkController
     */
    protected function prependCssFiles($files)
    {
      $files = func_get_args();
      $this->_css_files = array_merge($files, $this->_css_files);
      return $this;
    }

    /**
     * Agrega una o más rutas de archivos JS al final de la lista de archivos JS
     * para incluir con includeJSFiles(), devuelve la referencia al controller para
     * hacer linking
     * 
     * @param string $files,...
     * @return QuarkController
     */
    protected function appendJsFiles($files)
    {
      $files = func_get_args();
      $this->_js_files = array_merge($this->_js_files, $files);
      return $this;
    }

    /**
     * Agrega una o más rutas de archivos JS al inicio de la lista de archivos JS
     * para incluir con includeJsFiles(), devuelve la referencia al controller para
     * hacer linking
     * 
     * @param string $files,...
     * @return QuarkController
     */
    protected function prependJsFiles($files)
    {
      $files = func_get_args();
      $this->_js_files = array_merge($files, $this->_js_files);
      return $this;
    }

    protected function includeCssFiles($full_url = true)
    {
      $included = array();
      foreach($this->_css_files as $file){
        if(array_search($file, $included) === false){
          $included[] = $file;

          // Definir el atributo "media", por default es "all"
          $file_info = explode(';', $file);
          if(!isset($file_info[1])){
            $file_info[] = 'all';
          }
          echo '<link rel="stylesheet" type="text/css" media="'
            , $file_info[1], '"', ' href="'
            , ($full_url ? $this->QuarkURL->getBaseURL() : '')
            , 'application/public/css/', $file_info[0],'" />', PHP_EOL;
        }
      }
    }

    protected function includeJsFiles($full_url = true)
    {
      $included = array();

      echo '<script type="text/javascript" src="'
        , ($full_url ? $this->QuarkURL->getBaseURL() : '')
        , (!QUARK_FRIENDLY_URL ? '?' : '')
        , 'quark/quark-include-js"></script>';

      foreach($this->_js_files as $file){
        if(array_search($file, $included) === false){
          $included[] = $file;
          echo '<script type="text/javascript" src="'
            , ($full_url ? $this->QuarkURL->getBaseURL() : '')
            , 'application/public/js/', $file, '"></script>', PHP_EOL;
        }
      }
    }

    /**
     * Renderiza la vista especificada por $view mapeando las variables
     * de $vars, sí $return es true, devuelve el render de la vista como
     * un string, de lo contrario imprime el render de la vista.
     * 
     * En caso de estar utilizando multi-lenguaje configura el objeto
     * QuarkLang para obtener los textos del archivo de definición de
     * textos correspondientes al lenguaje en curso.
     * 
     * @see QuarkLang::setResource()
     * @param string $view Ruta del archivo de la vista que se va a renderizar
     * si no se especifica se utiliza la vista correspondiente
     * al controlador/accion en curso.
     * @param array $vars Array asociativo con el mapa de variables que se pasarán
     * a la vista.
     * @param bool $return Flag para definir si el render será devuelto como un
     * string o sera impreso en el buffer de salida.
     */
    protected function renderView($view = null, $vars = null, $return = false)
    {
        if ($view == null) {
            $view = Quark::getCalledControllerName(). '/'.
              Quark::getCalledActionName(). '.php';
        }
        
        /* Configurar QuarkLang */
        if (QUARK_MULTILANG) {
            $lang_resource = explode('.', $view);
            array_pop($lang_resource);
            $lang_resource = implode('.', $lang_resource);
            
            $old_lang_resource = $this->QuarkLang->setResource($lang_resource);
        }
        
        /* Agregar variables a la lista */
        if (is_array($vars)) {
            $this->_view_vars = array_merge($this->_view_vars, $vars);
        }
        
        /* Mapear variables de la lista al entorno actual */
        foreach ( $this->_view_vars as $var => &$val ) {
            $$var = $val;
        }
        
        /* Renderizar vista */
        ob_start();
        require 'views/' . $view;
        $render = ob_get_contents();
        ob_end_clean();
        
        /* Re-configurar QuarkLang */
        if (QUARK_MULTILANG) {
            $this->QuarkLang->setResource($old_lang_resource);
        }
        
        /* Imprimir o devolver el render */
        if ($return) {
            return $render;
        } else {
            echo $render;
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
    public function __getActionAccessLevel($action_name)
    {
        if (!isset($this->_actions_access_level[$action_name])) {
            return $this->_default_access_level;
        } else {
            return $this->_actions_access_level[$action_name];
        }
    }
    
    protected function setAjaxResponse($data, $message = '', $error = false)
    {
      $this->_ajax_result = $data;
      $this->_ajax_message = $message;
      $this->_ajax_error   = $error;
    }

    /**
     * Establece que la sesión de usuario actual no
     * tiene permisos para la solicitud ajax invocada.
     * 
     * @see QuarkSess::setAccessLevel()
     * @see QuarkController::setDefaultAccessLevel()
     * @see QuarkController::setActionsAccessLevel()
     */
    protected function setAjaxAccessDenied()
    {
        $this->_ajax_access_denied = true;
    }
    
    /**
     * Establece que el recurso de la solicitud ajax no existe.
     */
    protected function setAjaxNotFound()
    {
        $this->_ajax_not_found = true;
    }

    public function __sendAjaxResponse()
    {
      header('Content-Type:application/json;charset=utf-8');
      echo json_encode(array(
        'access_denied' => $this->_ajax_access_denied,
        'not_found'     => $this->_ajax_not_found,
        
        'data' => array(
            'result'  => $this->_ajax_result,
            'message' => $this->_ajax_message,
            'error'   => $this->_ajax_error,
        ),

        // Compatibilidad con Quark::obHandler() y Quark.ajax.complete()
        'error' => false,
      ));
    }
    
    /**
     * Este metodo es invocado cuando se intenta acceder a una
     * acción de nivel mayor al nivel de la sesión actual.
     * 
     * @see QuarkSess::setAccessLevel()
     * @see Quark::bigBang()
     */
    public function __quarkAccessDenied()
    {
        if (QUARK_AJAX) {
            $this->setAjaxAccessDenied();
        } else {
            $this->renderView('quark-access-denied.php');
        }
    }
    
    /**
     * Este metodo es invocado cuando no se encuentra la acción
     * solicitada mediante la URL
     */
    public function __quarkNotFound()
    {
        if (QUARK_AJAX) {
            $this->setAjaxNotFound();
        } else {
            $this->renderView('quark-not-found.php');
        }
    }

    public function quarkIncludeJs()
    {
        header('Content-Type:text/javascript;charset=utf8');
        echo file_get_contents('system/public/js/jquery.js');
        require 'system/public/js/quark.js';
    }
}
