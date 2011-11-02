<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para manipular cadenas y realizar algunas tareas comunes.
 * @author sahib
 */
class QuarkString
{
    /**
     * Charset utilizado en las funciones de manejo de cadenas, utf-8 por default.
     * @var string
     */
    private $_charset = 'utf-8';
    
    /**
     * Define el charset que se utilizara en las funciones
     * 
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
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
        return preg_replace('/(^\/|\/$)/', '', preg_replace('/\/{2,}/', '/', str_replace("\\", '/', $path)));
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
        return implode('', array_map('ucfirst', explode($separator, strtolower($str))));
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
        $words = explode($separator, strtolower($str));
        $first_word = array_shift($words);
        return $first_word . implode('', array_map('ucfirst', $words));
    }
    
    /**
     * Convierte un array en una cadena de valores separados por coma.
     * Devuelve la cadena de argumentos listos para ser utilizados con
     * eval() como argumentos a una función o método.
     * 
     * @param array $array
     * @return string
     */
    public function arrayToArgumentsString($array)
    {
        $arguments = array();
        foreach ( $array as $val ) {
            if (is_numeric($val))
                $arguments[] = $val;
            else
                $arguments[] = '"' . addslashes($val) . '"';
        }
        return implode(',', $arguments);
    }
    
    /**
     * Aplica htmlentities a una cadena y la imprime, si $return es TRUE
     * devuelve la cadena procesada sin imprimirla.
     * Utiliza el charset definido por QuarkString::setCharset()
     * 
     * @param string $str
     * @param bool $return
     * @return string
     */
    public function esc($str, $return = FALSE)
    {
        $str = htmlentities($str, ENT_QUOTES, $this->_charset);
        if ($return)
            return $str;
        else
            echo $str;
    }
    
    /**
     * Normaliza una cadena de texto para ser utilizada en URL o nombres de archivo.
     * @param string $str cadena a normalizar
     * @return string cadena normalizada
     */
    public function normalize($str)
    {
        $str = mb_strtolower($str, 'utf-8');
        $str = strtr(utf8_decode($str), utf8_decode('áàäâéèëêíìïîóòöôúùüûñ'), 'aaaaeeeeiiiioooouuuun');
        $str = preg_replace('/ {2,}/', ' ', $str);
        $str = str_replace(' ', '-', $str);
        $str = str_replace('&', '-y-', $str);
        return preg_replace('/[^[:alnum:]-]/', '', $str);
    }
}