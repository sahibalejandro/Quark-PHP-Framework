<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase es utilizada para renderizar vistas php y generando el código HTML
 * necesario, tambien para manejar los recursos de la pagina
 * como archivos css, js, meta tags, etc.
 *
 * @see QuarkController
 * @see QuarkLang
 */
class QuarkView
{
	/**
	 * Array asociativo
	 * Almacena las listas de recursos para las vistas, como archivos css, js, meta
	 * tags, etc.
	 *
	 * @var array
	 */
	private $_html_resources = array();
	/**
	 * Array asociativo
	 * Almacena la lista de metodos para peticiones AJAX que seran definidos en
	 * QuarkView->includeJsFiles()
	 *
	 * @var array
	 */
	private $_ajax_methods = array();

	/**
	 * Array asociativo con la lista de variables
	 * que serán mapeadas a las vistas
	 *
	 * @var array
	 */
	private $_vars_map = array();

	/**
	 * Flag para incluir los archivos CSS y JS de Quark UI
	 *
	 * @var bool
	 */
	private static $_load_quark_ui_resources = FALSE;

	/**
	 * @var QuarkString
	 */
	public $QuarkString;

	/**
	 * @var QuarkUrl
	 */
	public $QuarkUrl;

	/**
	 * @var QuarkAjax
	 */
	public $QuarkAjax;

	/**
	 * @var QuarkLang
	 */
	public $QuarkLang;

	protected $auto_escape_vars = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (Quark::getConfigVal('autoload_quarkui') and !self::$_load_quark_ui_resources) {
			$this->includeQuarkUIResources();
		}
	}

	/**
	 * Inserta los recursos $resources en $this->_html_resources, dentro
	 * de la sección especificada por $type
	 *
	 * @param string $type Tipo de recurso, puede ser 'css', 'js', 'metatag', ...
	 * @param array $resources Lista de recursos
	 * @param bool $prepend Flag para definir si los recursos seran
	 * agregados al inicio o al final de la lista.
	 */
	private function _addHtmlResource($type, $resources, $prepend = FALSE)
	{
		/* Invertir el orden de los recursos si va a realizar prepend */
		if ($prepend) {
			$resources = array_reverse($resources);
		}

		/* Inicializar el array de recursos */
		if (!isset($this->_html_resources[$type])) {
			$this->_html_resources[$type] = array();
		}

		/* Iteración sobre los recursos para agregarlos a $this->_html_resources */
		foreach ($resources as $resource) {
			if (FALSE == array_search($resource, $this->_html_resources[$type])) {
				if (!$prepend)
					$this->_html_resources[$type][] = $resource;
				else {
					array_unshift($this->_html_resources[$type], $resource);
				}
			}
		}
	}

	/**
	 * Agrega recursos CSS al final de la lista
	 */
	public function appendCssFiles()
	{
		$args = func_get_args();
		$this->_addHtmlResource('css', $args);
	}

	/**
	 * Agrega recursos CSS al inicio de la lista
	 */
	public function prependCssFiles()
	{
		$args = func_get_args();
		$this->_addHtmlResource('css', $args, TRUE);
	}

	/**
	 * Agrega recursos JS al final de la lista
	 */
	public function appendJsFiles()
	{
		$args = func_get_args();
		$this->_addHtmlResource('js', $args);
	}

	/**
	 * Agrega recursos JS al inicio de la lista
	 */
	public function prependJsFiles()
	{
		$args = func_get_args();
		$this->_addHtmlResource('js', $args, TRUE);
	}

	public function includeQuarkUIResources()
	{
		self::$_load_quark_ui_resources = TRUE;
	}

	/**
	 * Define la lista de metodos para peticiones AJAX que serán
	 * definidos automaticamente en el scope javascript con
	 * QuarkView->includeJsFiles()
	 *
	 * @param string $controller_name Nombre del controlador (sin prefijo
	 * "Controller")
	 * @param string,... Lista de metodos para agregar.
	 */
	public function defineAjaxMethods($controller_name)
	{
		$methods = func_get_args();
		$methods = array_slice($methods, 1);

		if (empty($methods)) {
			$methods = preg_grep('/^ajax/', get_class_methods($controller_name . 'Controller'));
		}

		if (isset($this->_ajax_methods[$controller_name])) {
			$methods = array_merge($this->_ajax_methods[$controller_name], $methods);
			$methods = array_unique($methods);
		}

		$this->_ajax_methods[$controller_name] = $methods;
	}

	/**
	 * Imprime el código HTML para incluir todos los recursos CSS agregados
	 * con $this->appendCssFiles()/prependCssFiles()
	 */
	public function includeCssFiles()
	{
		$base_url = $this->QuarkUrl->baseUrl('', TRUE);

		/* Incluir CSS para Quark UI */
		if (self::$_load_quark_ui_resources) {
			echo '<link rel="stylesheet" type="text/css" href="', $base_url, 'system/public/css/quark-ui.css" />', PHP_EOL;
		}

		/* Solo si existen recursos CSS en la lista */
		if (isset($this->_html_resources['css'])) {

			/* Iterar sobre los recursos para imprimir su tag HTML correspondiente */
			foreach ($this->_html_resources['css'] as $css_resource) {
				$css_resource = explode(';', $css_resource);
				$css_resource = array_map('trim', $css_resource);
				$css_file = $css_resource[0];
				$css_media = (!isset($css_resource[1]) ? 'all' : $css_resource[1]);
				$css_file_url = $base_url . 'application/public/css/' . $css_file;

				echo '<link rel="stylesheet" type="text/css" href="', $css_file_url, '" media="', $css_media, '" />', PHP_EOL;
			}
		}
	}

	/**
	 * Imprime el código HTML para incluir todos los recursos JS agregados
	 * con $this->appendCssFiles()/prependCssFiles()
	 * Tambien escribe el código HTML necesario para incluir jQuery
	 * y definir el objeto Quark de JavaScript
	 */
	public function includeJsFiles()
	{
		$base_url = $this->QuarkUrl->baseUrl('', TRUE);

		/* Definir Quark y metodos ajax */
		echo '<script type="text/javascript">', PHP_EOL;

		/* Objeto Quark */
		$quark_data = array(
				'BASE_URL' => $this->QuarkUrl->baseUrl('', TRUE),
				'LANG' => $this->QuarkUrl->getPathInfo()->lang,
				'DEBUG' => QUARK_DEBUG,
				'MULTILANG' => QUARK_MULTILANG,
				'LANG_ON_SUBDOMAIN' => QUARK_LANG_ON_SUBDOMAIN,
				'FRIENDLY_URL' => QUARK_FRIENDLY_URL
		);
		echo 'var Quark=',  json_encode($quark_data), ';', PHP_EOL;

		/* Metodos para peticiones ajax */
		foreach ($this->_ajax_methods as $controller_name => $methods) {
			$def_methods_list = array();
			echo 'var ', $controller_name, 'Controller={', PHP_EOL;
			foreach ($methods as $method_name) {
				$def_methods_list[] = "'$method_name':function(_){Quark.ajax('$controller_name/$method_name',_);}";
			}
			echo implode(',' . PHP_EOL, $def_methods_list), '};', PHP_EOL;
		}

		echo '</script>', PHP_EOL;

		/* Incluir jQuery */
		echo '<script type="text/javascript" src="', $base_url, 'system/public/js/jquery.js"></script>', PHP_EOL;

		/* Incluir Quark */
		echo '<script type="text/javascript" src="', $base_url, 'system/public/js/quark.js"></script>', PHP_EOL;

		/* Incluir Quark UI */
		if (self::$_load_quark_ui_resources) {
			echo '<script type="text/javascript" src="', $base_url, 'system/public/js/quark-ui.js"></script>', PHP_EOL;
		}

		/* Solo si existen recursos JS en la lista */
		if (isset($this->_html_resources['js'])) {

			/* Iterar sobre los recursos para imprimir su tag HTML correspondiente */
			foreach ($this->_html_resources['js'] as $js_file_name) {
				$js_file_url = $base_url . 'application/public/js/' . $js_file_name;

				echo '<script type="text/javascript" src="', $js_file_url, '"></script>', PHP_EOL;
			}
		}
	}

	/**
	 * Renderiza la vista especificada por $view mapeando las variables
	 * de $vars, sí $return es TRUE, devuelve el render de la vista como
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
	public function render($view = '', $vars = array(), $return = FALSE)
	{
		if (empty($view)) {
			$view = $this->QuarkUrl->getPathInfo()->controller . '/' . $this->QuarkUrl->getPathInfo()->action . '.php';
		}

		/* Configurar QuarkLang */
		if (QUARK_MULTILANG) {
			$lang_resource = explode('.', $view);
			array_pop($lang_resource);
			$lang_resource = implode('.', $lang_resource);

			$old_lang_resource = $this->QuarkLang->setResource($lang_resource);
		}

		/* Agregar variables a la lista */
		if (!empty($vars)) {

			// Escapar automaticamente las variables si esta establecido con
			// $this->setAutoEscapeVars()
			if ($this->auto_escape_vars) {
				foreach ($vars as $k => $var) {
					if (!is_numeric($var)) {
						$vars[$k] = $this->QuarkString->esc($var, TRUE);
					}
				}
			}

			$this->_vars_map = array_merge($this->_vars_map, $vars);
		}

		/* Mapear variables de la lista al entorno actual */
		foreach ($this->_vars_map as $var => &$val) {
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

	public function setAutoEscapeVars($auto_escape)
	{
		$this->auto_escape_vars = $auto_escape;
	}

}
