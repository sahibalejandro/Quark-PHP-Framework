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
 * Clase para cargar archivos de definición de cadenas de en diferentes lenguajes
 * y obtener los textos dependiendo del lenguaje especificado.
 * 
 * @author Sahib J. Leo <sahib.alejandro@gmail.com>
 */
class QuarkLang
{
  /**
   * Dirección del "resource path" que se utilizara por QuarkLang->get()
   * @var string
   */
  private $_resource_path;
  /**
   * Array asociativo con todas las cadenas
   * de lenguaje cargadas bajo demanda.
   * 
   * @var array
   */
  private $_loaded_langs = array();
  /**
   * Prefijo de lenguaje
   * @var string
   */
  private $_default_lang;

  /**
   * Contructor
   * @return QuarkLang
   */
  public function __construct()
  {
    $this->_default_lang = Quark::inst('QuarkURL')->getPathInfo()->lang;
  }

  /**
   * Devuelve el texto de lenguaje definido por $key dentro del
   * archivo de lenguaje establecido con QuarkLang->setResource()
   * Se puede forzar el lenguaje con $lang
   * Si $key es un "resource path" se tomará de ahí.
   * 
   * @param string $key
   * @param string $lang
   */
  public function get($key, $lang = null)
  {
    $lang = (empty($lang) ? $this->_default_lang : $lang);
    
    if (strpos($key, '/') === false) {
      $resource_path = $this->_resource_path;
    } else {
      $key_parts = explode('/', $key);
      $key = array_pop($key_parts);
      $resource_path = implode('/', $key_parts);
    }
    
    $lang_file = $resource_path . '-' . $lang . '.php';
    
    /*
     * Cargar archivo de lenguaje
     */
    if (!isset($this->_loaded_langs[$lang_file])) {
      if (is_file(QUARK_APP_PATH . "/langs/$lang_file")) {
        $lang = array();
        require QUARK_APP_PATH . "/langs/$lang_file";
        $this->_loaded_langs[$lang_file] = $lang;
      }
    }
    
    /*
     * Generar texto de salida
     */
    if (!isset($this->_loaded_langs[$lang_file][$key])) {
      $text = '{' . $lang_file . '/' . $key . '}';
    } else {
      $text = $this->_loaded_langs[$lang_file][$key];
    }
    
    /*
     * Devolver texto
     */
    return $text;
  }

  /**
   * Define la ruta de donde se tomará el archivo de lenguaje.
   * Ejemplo: "controller/action"
   * Devuelve el path antiguo.
   * 
   * @param string $resource_path
   * @return string
   */
  public function setResource($resource_path)
  {
    $bk = $this->_resource_path;
    $this->_resource_path = $resource_path;
    return $bk;
  }
}
