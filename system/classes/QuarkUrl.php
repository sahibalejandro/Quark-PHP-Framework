<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para crear URLs validas en la aplicación actual.
 * @author sahib
 */
class QuarkUrl
{

    /**
     * @var Object
     */
    private static $_PathInfo;

    /**
     * @var string
     */
    private static $_base_url;

    /**
     * Devuelve un objeto PathInfo con los datos
     * de la URL de entrada.
     *
     * @return Object
     */
    public function getPathInfo()
    {
        if (self::$_PathInfo == NULL) {
            /*
             * Obtener el path info desde la query string
             */
            if (QUARK_FRIENDLY_URL) {
                $path_info = $_GET['quark_path_info'];
            } else {
                $path_info = key($_GET);
            }

            /* Cargar rutas para modificar el path info */
            $routes = array();
            require 'config/routes.php';

            foreach ($routes as $pattern => $new_route) {
                $pattern = "#^$pattern$#";
                if (preg_match($pattern, $path_info)) {
                    $path_info = preg_replace($pattern, $new_route, $path_info);
                    break;
                }
            }

            /* limpiar el path info */
            $path_info = Quark::inst('QuarkString')->cleanPath($path_info);

            /* crear el array con las partes del path info */
            if (empty($path_info)) {
                $path_info_parts = array();
            } else {
                $path_info_parts = explode('/', $path_info);
            }

            /*
             * Configurar el elemento "lang" (indice: 0) del path info
             */
            if (!QUARK_MULTILANG) {
                array_unshift($path_info_parts, 'no_lang');
            } else {
                $langs = Quark::getConfigVal('langs');

                /* obtener el lenguaje desde la query string */
                if (!QUARK_LANG_ON_SUBDOMAIN) {
                    if (!isset($path_info_parts[0]) or array_search($path_info_parts[0], $langs) === FALSE) {
                        array_unshift($path_info_parts, $langs[0]);
                    }
                } else {
                    /* obtener el lenguaje desde el subdominio del host */
                    $host_parts = explode('.', $_SERVER['HTTP_HOST']);

                    $lang = $host_parts[0];
                    if (array_search($lang, $langs) === FALSE) {
                        $lang = $langs[0];
                    }

                    array_unshift($path_info_parts, $lang);
                }
            }

            /* crear array fixeado del path info */
            $path_info_parts = array_pad(explode('/', implode('/', $path_info_parts), 4), 4, NULL);

            /* Valores por default para el path_info */
            if (empty($path_info_parts[1])) {
                $path_info_parts[1] = 'home';
            }

            if (empty($path_info_parts[2])) {
                $path_info_parts[2] = 'index';
            }

            if (empty($path_info_parts[3])) {
                $path_info_parts[3] = array();
            } else {
                $path_info_parts[3] = explode('/', $path_info_parts[3]);
            }

            /* Cargar objeto path info y finalizar */
            list(self::$_PathInfo->lang, self::$_PathInfo->controller, self::$_PathInfo->action, self::$_PathInfo->arguments) = $path_info_parts;
        }
        return self::$_PathInfo;
    }

    /**
     * Imprime una URL con el prefijo de lenguaje $lang haciendo
     * referenci al controlador y accion actuales.
     * Si $return es TRUE devuelve la URL en lugar de imprimirla.
     *
     * @param string $lang
     * @param bool $return
     * @return string
     */
    public function urlSwitchLang($lang, $return = FALSE)
    {

        $action = self::getPathInfo()->action == 'index' ? '' : self::getPathInfo()->action;
        $controller = $action != '' ? self::getPathInfo()->controller : (self::getPathInfo()->controller == 'home' ? '' : self::getPathInfo()->controller);
        $url = $controller . ($action == '' ? '' : "/$action");
        $url = $this->url($url, $lang, TRUE);

        if ($return) {
            return $url;
        } else {
            echo $url;
        }
    }

    /**
     * Genera la Base URL con prefijo $lang opcional
     * @param string $lang
     * @return string
     */
    private function _makeBaseUrl($lang = NULL)
    {

        $host = $_SERVER['HTTP_HOST'];

        if (QUARK_MULTILANG and QUARK_LANG_ON_SUBDOMAIN) {

            /* Tomar el lenguaje del pathinfo si no se especificó */
            if ($lang == NULL) {
                $lang = self::getPathInfo()->lang;
            }

            /* Separar el host name para verificar si el subdominio es un prefijo
             * de lenguaje */
            $host_parts = explode('.', $_SERVER['HTTP_HOST']);

            if (array_search($host_parts[0], Quark::getConfigVal('langs')) !== FALSE) {
                /* El subdominio es el prefijo del lenguaje, lo reemplazamos por
                 * el lenguaje definido como argumento */
                $host_parts[0] = $lang;
            } else {
                /* El subdominio no es un prefijo de lenguaje, insertamos el
                 * prefijo del
                 * lenguaje actual al inicio de las partes de host name para
                 * despues pegarlo */
                array_unshift($host_parts, $lang);
            }

            /* Pegamos el host name de nuevo y ya tendrá el prefijo del lenguaje
             * en caso de ser necesario */
            $host = implode('.', $host_parts);
        }

        /* Definimos el protocolo para armar la URL completa */
        $protocol = (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') !== FALSE ? 'https' : 'http');

		$script_path = Quark::inst('QuarkString')->cleanPath(dirname($_SERVER['SCRIPT_NAME']));

        $base_url = $protocol . '://' . $host . '/';
		
		if(!empty($script_path)){
			$base_url .= $script_path.'/';
		}
		
		return $base_url;
    }

    /**
     * Imprime la URL base con $lang opcional, si $return es TRUE
     * devuelve la URL en lugar de imprimirla.
     *
     * @param string $lang
     * @param bool $return
     * @return string
     */
    public function baseUrl($lang = NULL, $return = FALSE)
    {

        if (self::$_base_url == NULL) {
            self::$_base_url = $this->_makeBaseUrl();
        }

        if ($lang != NULL) {
            $url = $this->_makeBaseUrl($lang);
        } else {
            $url = self::$_base_url;
        }

        if ($return) {
            return $url;
        } else {
            echo $url;
        }

    }

    /**
     * Imprime una URL en base a $url y $lang opcional.
     * Si $return es TRUE devuelve la URL en lugar de imprirla.
     *
     * @param string $url
     * @param string $lang
     * @param bool $return
     * @return string
     */
    public function url($url, $lang = NULL, $return = FALSE)
    {
        $url = Quark::inst('QuarkString')->cleanPath($url);
        $base_url = $this->baseUrl($lang, TRUE);

        /*
         * Agregar prefijo de lenguaje si estamos en multilenguaje
         * y el lenguaje no esta en el subdominio.
         */
        if (QUARK_MULTILANG and !QUARK_LANG_ON_SUBDOMAIN) {
            if ($lang == NULL) {
                $lang = self::getPathInfo()->lang;
            }
            $url = $lang . '/' . $url;
        }

        /*
         * Reemplazar el signo "?" de argumentos GET por el
         * signo "&" en la url solo en caso de NO utilizar friendly url
         * ya que el signo "?" será automaticamente agregado.
         */
        if (!QUARK_FRIENDLY_URL and strpos($url, '?') !== FALSE) {
            $url = str_replace('?', '&', $url);
        }

        /*
         * Generar URL de salida.
         */
        $url = $base_url . ((!QUARK_FRIENDLY_URL and !empty($url)) ? '?' : '') . $url;

        if ($return) {
            return $url;
        } else {
            echo $url;
        }

    }

}
