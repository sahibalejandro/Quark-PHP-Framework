<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para manipular cadenas y realizar algunas tareas comunes.
 * @author sahib
 */
class QuarkStr
{
  /**
   * Charset utilizado en las funciones de manejo de cadenas, utf-8 por default.
   * @var string
   */
  private static $_charset = 'utf-8';
  
  /**
   * Define el charset que se utilizara en las funciones
   * 
   * @param string $charset
   */
  public static function setCharset($charset)
  {
    self::$_charset = $charset;
  }
  
  /**
   * Convierte el charset de $str desde el charset $from a $to
   * Si $str es un array se aplica a cada uno de sus elementos, en forma recursiva.
   * 
   * @access public
   * @param string|array &$str
   * @param string $from
   * @param string $to
   */
  public function convertCharset(&$str, $from = 'UTF-8', $to = 'ISO-8859-1')
  {
    if(is_string($str)){
      return iconv($from, $to, $str);
    } else if(is_array($str)){
      foreach($str as $i => $str2){
        $str[$i] = $this->convertCharset($str2, $from, $to);
      }
      return $str;
    }
  }

  /**
   * Limpia una ruta eliminando las diagonales multiples y las diagonales
   * al inicio y al final de la cadena.
   * Devuelve el path limpio.
   * 
   * @param string $path
   * @return string
   */
  public function cleanPath($path)
  {
    return preg_replace(
      '/(^\/|\/$)/',
      '',
      preg_replace('/\/{2,}/', '/', str_replace("\\", '/', $path))
    );
  }
  
  /**
   * Transforma una serie de palabras separadas por $separator en una
   * sola cadena en formato UpperCamelCase
   * Devuelve la cadena transformada.
   * 
   * @param string $str
   * @param string $separator
   * @return string
   */
  public function toUpperCamelCase($str, $separator = '-')
  {
    return implode('', array_map(
      'ucfirst',
      explode($separator, strtolower($str))
    ));
  }
  
  /**
   * Transforma una serie de palabras separadas por $separator en una
   * sola cadena en formato lowerCamelCase
   * Devuelve la cadena transformada.
   * 
   * @param string $str
   * @param string $separator
   * @return string
   */
  public function toLowerCamelCase($str, $separator = '-')
  {
    $camel_case = $this->toUpperCamelCase($str, $separator);
    $camel_case{0} = strtolower($camel_case{0});
    return $camel_case;
  }

  /**
   * Convierte una cadena de formato "UpperCamelCase" o "lowerCamelCase" a formato
   * "lower-case", ej: "HelloWorld" => "hello-world", utilizando $glue como
   * separador de palabras.
   */
  public function unCamelCase($string, $glue = '-')
  {
    preg_match_all('/[A-Z][^A-Z]+/', ucfirst($string), $matches);
    return implode($glue, array_map('strtolower', $matches[0]));
  }
  
  /**
   * Aplica htmlentities a una cadena y la devuelve.
   * Utiliza el charset definido por QuarkStr::setCharset()
   * 
   * @param string $str
   * @return string
   */
  public function esc($str)
  {
    return htmlentities($str, ENT_QUOTES, self::$_charset);
  }
  
  /**
   * Normaliza una cadena de texto para ser utilizada en URL o nombres de archivo.
   * @param string $str cadena a normalizar
   * @return string cadena normalizada
   */
  public function normalize($str)
  {
    $str = mb_strtolower($str, 'utf-8');
    // utf8_decode por que este script esta en formato UTF-8
    $str = strtr(utf8_decode($str), utf8_decode('áàäâéèëêíìïîóòöôúùüûñ'), 'aaaaeeeeiiiioooouuuun');
    $str = preg_replace('/ {2,}/', ' ', $str);
    $str = str_replace(' ', '-', $str);
    $str = str_replace('&', '-y-', $str);
    return preg_replace('/[^[:alnum:]-]/', '', $str);
  }
  
  /**
   * Cuts a string at the $max_words count, words are separated only by spaces.
   * 
   * @param string $string String to cut
   * @param int $max_words Max number of words.
   * @return string The result string.
   */
  public function cutAtWord($string, $max_words)
  {
    return implode(' ',
      array_slice(preg_split('/ +/', $string, $max_words + 1, null), 0, $max_words)
    );
  }

  /**
   * Hace un resumen de un texto, limitandolo a $max_words palabras.
   * Cuando el texto tiene más de $max_words palabras se corta y se
   * concatena $suffix al final del texto.
   * Si el texto no tiene más de $max_words no se corta ni se agrega $suffix
   * 
   * @param string $text Texto
   * @param int $max_words Número máximo de palabras
   * @param string $suffix Sufijo para el corte
   */
  public function resumeText($text, $max_words = 10, $suffix = '...')
  {
    $words = str_word_count($text, 1);
    if (count($words) <= $max_words) {
      return $text;
    } else {
      return array_slice($words, 0, $max_words).$suffix;
    }
  }
}
