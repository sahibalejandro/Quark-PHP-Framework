<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase bootstrap
 * @author sahib
 */
class Quark
{
    /**
     * Directivas de configuración
     * @see Quark::getConfigVal()
     * @var array
     */
    private static $_config;

    /**
     * Versión del framework
     * @var string
     */
    const VERSION = '3.0.6';

    /**
     * Versión minima de PHP necesaria
     * @var string
     */
    const PHP_MIN_VERSION = '5.1';

    /**
     * Bootstrap
     */
    public static function bigBang()
    {
        ini_set('display_errors', 1);

        /* --------------------------------------------------
         * Validar versión minima de PHP 5.1
         */
        if (version_compare(PHP_VERSION, self::PHP_MIN_VERSION) < 0) {
            die('Quark necesita <b>PHP ' . self::PHP_MIN_VERSION . '</b> o mayor.<br />Versión actual de PHP: <b>' . PHP_VERSION . '</b>');
            exit();
        }

        /* --------------------------------------------------
         * Autoload classes
         */
        function __autoload($class_name)
        {
            foreach (Quark::getConfigVal('class_paths') as $path) {
                if (is_file("$path/$class_name.php")) {
                    require "$path/$class_name.php";
                    break;
                }
            }
        }

        /* --------------------------------------------------
         * Inicializar output buffer handler
         */
        ob_start(array('Quark', 'obHandler'));

        /* --------------------------------------------------
         * Definir include paths, estos sirven para incluir archivos genericos ya
         * sea de "application" o "system" como el config, los includes o las
         * vistas.
         */
        $include_paths = PATH_SEPARATOR . 'application';
        $include_paths .= PATH_SEPARATOR . 'system';

        set_include_path(get_include_path() . PATH_SEPARATOR . $include_paths);

        /* --------------------------------------------------
         * Cargar configuración
         */
        $config = array();
        require 'config/config.php';
        self::$_config = $config;
        unset($config);

        /* --------------------------------------------------
         * Configurar el entorno
         */
        ini_set('error_prepend_string', '<quarkerror>');
        ini_set('error_append_string', '</quarkerror>');
        error_reporting(self::$_config['error_reporting']);

        if (function_exists('set_magic_quotes_runtime')) {
            set_magic_quotes_runtime(0);
        }

        /* --------------------------------------------------
         * Agregar class paths para __autoload()
         */
        self::$_config['class_paths'] = array_merge(array('system', 'system/classes', 'application/controllers', 'application/classes', 'application/models', 'system/controllers'), self::$_config['class_paths']);

        /* --------------------------------------------------
         * Constantes
         */
        define('QUARK_ROOT_PATH', dirname($_SERVER['SCRIPT_FILENAME']));
        define('QUARK_APP_PATH', QUARK_ROOT_PATH . '/application');
        define('QUARK_SYS_PATH', QUARK_ROOT_PATH . '/system');

        define('QUARK_DEBUG', self::$_config['debug']);
        define('QUARK_AJAX', isset($_GET['quark_ajax']));
        define('QUARK_MULTILANG', !empty(self::$_config['langs']));
        define('QUARK_FRIENDLY_URL', isset($_GET['quark_path_info']));
        define('QUARK_LANG_ON_SUBDOMAIN', self::$_config['lang_on_subdomain']);

        /* --------------------------------------------------
         * Leer path info
         */
        $PathInfo = self::inst('QuarkUrl')->getPathInfo();

        /* --------------------------------------------------
         * Agregar instancias automaticas para el controller y view
         */
        self::$_config['auto_instances'][] = 'QuarkUrl';
        self::$_config['auto_instances'][] = 'QuarkString';
        self::$_config['auto_instances'][] = 'QuarkSession';

        if (QUARK_MULTILANG) {
            self::$_config['auto_instances'][] = 'QuarkLang';
        }

        /* --------------------------------------------------
         * Agregar archivos de "includes" automaticos.
         */
        self::$_config['auto_includes'][] = 'jsonwrapper/jsonwrapper.php';

        foreach (self::$_config['auto_includes'] as $include_file) {
            require_once 'includes/' . $include_file;
        }

        /* --------------------------------------------------
         * Definir algunas clases
         */
        $QuarkString = new QuarkString();

        /* --------------------------------------------------
         * Instanciar controlador
         */
        $controller_name = $PathInfo->controller;
        $action_name = $PathInfo->action;

        if (!QUARK_AJAX) {
            $controller_name = $QuarkString->toUpperCamelCase($controller_name);
            $action_name = $QuarkString->toLowerCamelCase($action_name);
        }

        if (!class_exists("{$controller_name}Controller")) {
            $controller_name = 'QuarkNotFound';
        }

        $controller_class_name = $controller_name . 'Controller';
        /**
         * @var $Controller QuarkController
         */
        $Controller = new $controller_class_name();

        /* --------------------------------------------------
         * Invocar acción
         */
        if (!method_exists($Controller, $action_name)) {
            $action_name = 'quarkNotFound';
        }

        if ($Controller->QuarkSession->getAccessLevel() < $Controller->getActionAccessLevel($action_name)) {
            $action_name = 'quarkAccessDenied';
        }

        if (empty($PathInfo->arguments)) {
            $Controller->$action_name();
        } else {
            eval('$Controller->$action_name(' . $QuarkString->arrayToArgumentsString($PathInfo->arguments) . ');');
        }

        if (QUARK_AJAX) {
            $Controller->sendAjaxResponse();
        }

        ob_end_flush();
    }

    /**
     * Manejador del buffer de salida, realiza una busqueda de mensajes
     * de error, en caso de encontrarlos modifica el buffer de salida
     * para mostrar un vistoso mensaje de error.
     *
     * @return string
     */
    public static function obHandler($buffer)
    {
        if (preg_match_all('/<quarkerror>(.*)<\/quarkerror>/Us', $buffer, $matches) == FALSE) {
            return $buffer;
        } else {
            $errors = array_map('trim', $matches[1]);
            $errors = implode(PHP_EOL, $errors);
            define('QUARK_ERROR_MESSAGES', $errors);
			
			/* Log de mensajes de error */
			chdir(dirname($_SERVER['SCRIPT_FILENAME']));
			Quark::log(QUARK_ERROR_MESSAGES);

            if (QUARK_AJAX) {
                header('content-type:application/json;charset=utf-8');
                return '{"error":' . json_encode(QUARK_ERROR_MESSAGES) . '}';
            } else {
                chdir(dirname($_SERVER['SCRIPT_FILENAME']));
                $old_buffer = ob_get_contents();
                require 'views/quark-error.php';
                return substr(ob_get_contents(), strlen($old_buffer));
            }
        }
    }
	
	/**
	 * Envia un mensaje al archivo messages.log
	 */
	public static function log($message)
	{
		if( is_writable('messages.log') ){
			$PathInfo = Quark::inst('QuarkUrl')->getPathInfo();
			$full_message = '['.date('d-M-Y H:i:s').']';
			$full_message .= PHP_EOL.'lang='. $PathInfo->lang;
			$full_message .= ' controller='. $PathInfo->controller;
			$full_message .= ' action='. $PathInfo->action;
			$full_message .= ' arguments='. implode(', ', $PathInfo->arguments);
			$full_message .= PHP_EOL.$message.PHP_EOL.PHP_EOL;
			file_put_contents('messages.log', $full_message, FILE_APPEND);
		}
	}

    /**
     * Devuelve el valor de una directiva de configuración
     * especificada por $key.
     *
     * @return mixed
     */
    public static function getConfigVal($key)
    {
        return self::$_config[$key];
    }

    /**
     * Devuelve una instancia de $class_name para poder utilizar  "quick code"
     * @return Object
     */
    public static function inst($class_name)
    {
        return new $class_name();
    }

    /**
     * Realiza un var_dump() con formato y sale del script.
     */
    public static function dump($x)
    {
        echo '<pre>';
        var_dump($x);
        echo '</pre>';
        exit();
    }

}
