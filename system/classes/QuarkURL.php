<?php
/**
 * QuarkPHP Framework
 * Copyright 2012-2013 Sahib Alejandro Jaramillo Leo
 *
 * @author Sahib J. Leo <sahib.alejandro@gmail.com>
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @link    http://quarkphp.com
 */

/**
 * Clase para crear URLs validas en la aplicación actual.
 * @author Sahib J. Leo <sahib.alejandro@gmail.com>
 */
class QuarkURL
{
  /**
   * PathInfo que corresponde a la solicitud en la URL
   * @var Object
   */
  private static $_PathInfo;

  /**
   * PathInfo que corresponde a la solicitud para el controller y action
   * @var Object
   */
  private static $_RealPathInfo;

  /**
   * @var string
   */
  private static $_base_url;

  private static $_initialized = false;

  /**
   * Devuelve el objeto PathInfo real, si se especifica $real = false devuelve
   * el path info de la solicitud en la URL, que puede ser el no real.
   * 
   * @param bool $real TRUE por default.
   * @return object(controller,action,lang,arguments)
   */
  public function getPathInfo($real = true)
  {
    return $real ? self::$_RealPathInfo : self::$_PathInfo;
  }

  /**
   * Inicializa los objetos PathInfo y RealPathInfo
   * Este metodo debe ser invocado antes de cualquier uso de objetos QuarkURL
   * y solo puede ser ivocado una vez.
   */
  public static function init()
  {
    if (!self::$_initialized) {
      /*
       * Obtener el path info desde la query string
       */
      if (!QUARK_FRIENDLY_URL) {
        $path_info = key($_GET);
        if (strpos($path_info, '/'))
        array_shift($_GET);
      } else {
        $path_info = $_GET['quark_path_info'];
        unset($_GET['quark_path_info']);
        
        // Point to "home/index" if path info is "index.php"
        if($path_info == 'index.php'){
          $path_info = 'home/index';
        }
      }

      $Str            = new QuarkStr();
      $path_info      = $Str->cleanPath($path_info);
      $real_path_info = $path_info;

      // Modificar el path info si coincide con una ruta definida en la configuración
      foreach (Quark::getRoutes() as $pattern => $new_route) {
        $pattern = "#^$pattern$#";
        if (preg_match($pattern, $path_info)) {
          $real_path_info = preg_replace($pattern, $new_route, $path_info);
          break;
        }
      }

      self::$_PathInfo     = self::buildPathInfo($path_info);
      self::$_RealPathInfo = self::buildPathInfo($real_path_info);

      // Nos aseguramos que este metodo solo sea invocado una vez.
      self::$_initialized  = true;
    }
  }

  /**
   * Genera un objeto PathInfo a partir de $path_info
   * 
   * @return object(controller,action,lang,arguments)
   */
  private static function buildPathInfo($path_info)
  {
    /* limpiar el path info */
    $Str = new QuarkStr();
    $path_info = $Str->cleanPath($path_info);

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
        if (!isset($path_info_parts[0]) ||
          array_search($path_info_parts[0], $langs) === false
        ) {
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
    $path_info_parts = array_pad(
      explode('/', implode('/', $path_info_parts), 4),
      4,
      null
    );

    /* Valores por default para el path_info */
    if ($path_info_parts[1] == '') {
      $path_info_parts[1] = 'home';
    }

    if ($path_info_parts[2] == '') {
      $path_info_parts[2] = 'index';
    }

    if ($path_info_parts[3] == '') {
      $path_info_parts[3] = array();
    } else {
      $path_info_parts[3] = explode('/', $path_info_parts[3]);
    }

    /* Cargar objeto path info y finalizar */
    $PathInfo = new stdClass();
    list(
      $PathInfo->lang,
      $PathInfo->controller,
      $PathInfo->action,
      $PathInfo->arguments
    ) = $path_info_parts;

    return $PathInfo;
  }

  /**
   * Devuelve la URL actual, incluyendo sus variables GET
   * 
   * @param string $lang Sufijo de lenguaje (utilizado por getURLToSwitchLang())
   * @return string URL Actual
   */
  public function getActualURL($lang = null)
  {
    $FakePathInfo = self::getPathInfo(false);

    $action = $FakePathInfo->action == 'index'
      ? '' : $FakePathInfo->action;
      
    $controller = $action != ''
      ? $FakePathInfo->controller
      : ($FakePathInfo->controller == 'home'
        ? '' : $FakePathInfo->controller);
    $url = $controller . ($action == '' ? '' : "/$action");

    /* Agregar el http query, anteponiendo el signo "?" como debe ser normalmente.
     * El metodo getURL() se encargará de reemplazar el signo "?" por "&" si las
     * URL amigables no estan habilitadas. */
    if (!empty($_GET)) {
      $url .= '?'.http_build_query($_GET);
    }

    return $this->getURL($url, $lang);
  }

  /**
   * Devuelve la URL actual (incluyendo sus variables GET) pero con diferente
   * prefijo del lenguaje.
   *
   * @param string $lang Sufijo de lenguaje
   * @return string URL
   */
  public function getURLToSwitchLang($lang)
  {
    return $this->getActualURL($lang);
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
        $lang = self::getPathInfo(false)->lang;
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
         * prefijo del lenguaje actual al inicio de las partes de host name para
         * despues pegarlo */
        array_unshift($host_parts, $lang);
      }

      /* Pegamos el host name de nuevo y ya tendrá el prefijo del lenguaje
       * en caso de ser necesario */
      $host = implode('.', $host_parts);
    }

    /* Definimos el protocolo para armar la URL completa */
    $protocol = (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') !== false
      ? 'https' : 'http');

    $base_url = $protocol . '://' . $host . QUARK_APP_DIR
      . (QUARK_APP_DIR == '/' ? '' : '/');
        
    return $base_url;
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
   * Devuelve una URL en base a $url y $lang opcional.
   *
   * @param string $url
   * @param string $lang
   * @return string
   */
  public function getURL($url = '', $lang = null)
  {
    $url = Quark::inst('QuarkStr')->cleanPath($url);
    $base_url = $this->getBaseURL($lang);

    /*
     * Agregar prefijo de lenguaje si estamos en multilenguaje
     * y el lenguaje no esta en el subdominio.
     */
    if (QUARK_MULTILANG && !QUARK_LANG_ON_SUBDOMAIN) {
      if ($lang == null
        || array_search($lang, Quark::getConfigVal('langs')) === false
      ) {
        $lang = self::getPathInfo(false)->lang;
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
