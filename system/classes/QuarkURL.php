<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para crear URLs validas en la aplicación actual.
 * @author sahib
 */
class QuarkURL
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
        if (self::$_PathInfo == null) {
            /*
             * Obtener el path info desde la query string
             */
            if (QUARK_FRIENDLY_URL) {
                $path_info = $_GET['quark_path_info'];
                
                // Point to "home/index" if path info is "index.php"
                if($path_info == 'index.php'){
                    $path_info = 'home/index';
                }
            } else {
                $path_info = key($_GET);
            }
            
            
            /* Cargar rutas para modificar el path info */
            foreach (Quark::getRoutes() as $pattern => $new_route) {
                $pattern = "#^$pattern$#";
                if (preg_match($pattern, $path_info)) {
                    $path_info = preg_replace($pattern, $new_route, $path_info);
                    break;
                }
            }

            /* limpiar el path info */
            $path_info = Quark::inst('QuarkStr')->cleanPath($path_info);

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
                    if (!isset($path_info_parts[0]) || array_search($path_info_parts[0], $langs) === false) {
                        array_unshift($path_info_parts, $langs[0]);
                    }
                } else {
                    /* obtener el lenguaje desde el subdominio del host */
                    $host_parts = explode('.', $_SERVER['HTTP_HOST']);

                    $lang = $host_parts[0];
                    if (array_search($lang, $langs) === false) {
                        $lang = $langs[0];
                    }

                    array_unshift($path_info_parts, $lang);
                }
            }

            /* crear array fixeado del path info */
            $path_info_parts = array_pad(explode('/', implode('/', $path_info_parts), 4), 4, null);

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
     *  @deprecated Usar getURLToSwitchLang() en su lugar.
     */
    public function urlSwitchLang($l)
    {
        return $this->getURLToSwitchLang($l);
    }

    /**
     * Devuelve una URL con el prefijo de lenguaje $lang haciendo
     * referencia al controlador y accion actuales.
     *
     * @param string $lang
     * @return string
     */
    public function getURLToSwitchLang($lang)
    {
        $action = self::getPathInfo()->action == 'index' ? '' : self::getPathInfo()->action;
        $controller = $action != '' ? self::getPathInfo()->controller : (self::getPathInfo()->controller == 'home' ? '' : self::getPathInfo()->controller);
        $url = $controller . ($action == '' ? '' : "/$action");
        return $this->getURL($url, $lang);
    }

    /**
     * Genera la Base URL con prefijo $lang opcional
     * @param string $lang
     * @return string
     */
    private function _makeBaseUrl($lang = null)
    {

        $host = $_SERVER['HTTP_HOST'];

        if (QUARK_MULTILANG && QUARK_LANG_ON_SUBDOMAIN) {

            /* Tomar el lenguaje del pathinfo si no se especificó */
            if ($lang == null) {
                $lang = self::getPathInfo()->lang;
            }

            /* Separar el host name para verificar si el subdominio es un prefijo
             * de lenguaje */
            $host_parts = explode('.', $_SERVER['HTTP_HOST']);

            if (array_search($host_parts[0], Quark::getConfigVal('langs')) !== false) {
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
        $protocol = (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') !== false ? 'https' : 'http');

        $base_url = $protocol . '://' . $host . QUARK_APP_DIR
            . (QUARK_APP_DIR == '/' ? '' : '/');
		
		return $base_url;
    }

    /**
     * @deprecated Usar getBaseURL() en su lugar
     */
    public function baseUrl($lang = null)
    {
        return $this->getBaseURL($lang);
    }

    /**
     * Devuelve la URL base con $lang opcional
     *
     * @param string $lang
     * @return string
     */
    public function getBaseURL($lang = null)
    {
        // Generar $_base_url por primera vez
        if (self::$_base_url == null) {
            self::$_base_url = $this->_makeBaseUrl();
        }

        if ($lang != null) {
            // Generar una base url con un lenguaje en especifico
            $url = $this->_makeBaseUrl($lang);
        } else {
            // Utilizar la base url existente
            $url = self::$_base_url;
        }
        return $url;
    }

    /**
     * @deprecated Usar getURL() en su lugar.
     */
    public function url($url, $lang = null)
    {
        return $this->getURL($url, $lang);
    }

    /**
     * Devuelve una URL en base a $url y $lang opcional.
     *
     * @param string $url
     * @param string $lang
     * @return string
     */
    public function getURL($url, $lang = null)
    {
        $url = Quark::inst('QuarkStr')->cleanPath($url);
        $base_url = $this->getBaseURL($lang);

        /*
         * Agregar prefijo de lenguaje si estamos en multilenguaje
         * y el lenguaje no esta en el subdominio.
         */
        if (QUARK_MULTILANG && !QUARK_LANG_ON_SUBDOMAIN) {
            if ($lang == null) {
                $lang = self::getPathInfo()->lang;
            }
            $url = $lang . '/' . $url;
        }

        /*
         * Reemplazar el signo "?" de argumentos GET por el
         * signo "&" en la url solo en caso de NO utilizar friendly url
         * ya que el signo "?" será automaticamente agregado.
         */
        if (!QUARK_FRIENDLY_URL && strpos($url, '?') !== false) {
            $url = str_replace('?', '&', $url);
        }

        /*
         * Generar URL de salida.
         */
        return $base_url . ((!QUARK_FRIENDLY_URL && !empty($url)) ? '?' : '') . $url;
    }

}
