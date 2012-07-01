<?php
/**
 * Clase para generar codigo HTML
 * @author Sahib Alejandro Jaramillo Leo
 */
class QuarkHTML
{
  private $QuarkStr;

  public function __construct()
  {
    $this->QuarkStr = new QuarkStr();
  }

  /**
   * Genera código HTML de un select con nombre y id $nombre, las opciones
   * corresponden al array $options, donde el key es el valor del option y el valor
   * del array es el texto del option, $default es el valor (key) del option que sera
   * seleccionado por default, $attributes es un array key/valor de atributos html
   * para el tag select.
   * 
   * @access public
   * @param string $name
   * @param array $options
   * @param mixed $default
   * @param array $attributes
   * @return string Codigo HTML generado
   */
  public function select($name, $options, $default = null, $attributes = null)
  {
    $html = "<select name=\"$name\" id=\"$name\" "
      . $this->_expandAttributes($attributes) . ">";

    foreach($options as $key => $value){
      $html .= '<option value="'. $this->QuarkStr->esc($key). '"'.
        ($default == $key ? ' selected="selected"' : ''). '>'.
        $this->QuarkStr->esc($value). '</option>';
    }
    
    return $html .= '</select>';
  }

  /**
   * Devuelve el string de atributos HTML basado en el array key/value de $attributes
   * 
   * @access public
   * @param array $attributes
   * @return string
   */
  private function _expandAttributes($attributes)
  {
    if(!is_array($attributes)){
      return '';
    } else {
      $attrs = array();
      foreach($attributes as $attr => $value){
        $attrs[] = $attr. '="'. $this->QuarkStr->esc($value). '"';
      }
      return implode(' ', $attrs);
    }
  }
}
