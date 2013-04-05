<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para manipular imagenes con GD2
 * @author sahib
 */
class QuarkImage
{

  /**
   * Ancho de la imagen
   * 
   * @access private
   * @var int
   */
  private $_width = 0;

  /**
   * Alto de la imagen
   * 
   * @access private
   * @var int
   */
  private $_height = 0;

  /**
   * Tipo de la imagen, correspondiente a las constantes IMAGETYPE_* de PHP
   * 
   * @access private
   * @var int
   */
  private $_type = null;

  /**
   * Tipo MIME de la imagen
   * 
   * @access private
   * @var string
   */
  private $_mime_type = '';

  /**
   * Calidad de las imagenes JPEG de salida
   * 
   * @access private
   * @var int
   */
  private $_jpg_quality = 90;

  /**
   * Constructor, carga los datos de la imagen $file_path para despues manipularla,
   * si la imagen no existe o no puede ser cargada se lanza una excepcion
   * 
   * @access public
   * @param string $file_path
   * @throws QuarkImageException
   */
  public function __construct($file_path)
  {

    $img_info = getimagesize($file_path);
    
    if($img_info === false){
      throw new QuarkImageException(
        'No se pudo leer la informacion de la imagen '. $file_path,
        QuarkImageException::ERR_IMAGE_INFO);
    } else {
      // Asignar los valores a las propiedades
      $this->_file_path  = $file_path;
      $this->_width      = $img_info[0];
      $this->_height     = $img_info[1];
      $this->_image_type = $img_info[2];
      $this->_mime_type  = $img_info['mime'];
    }
  }

  /**
   * Devuelve la ruta del archivo de imagen, es la misma ruta
   * que fue definida en el constructor.
   * 
   * @access public
   * @return string
   */
  public function getFilePath()
  {
    return $this->_file_path;
  }

  /**
   * Devuelve el ancho de la imagen
   * 
   * @access public
   * @return int
   */
  public function getWidth()
  {
    return $this->_width;
  }

  /**
   * Devuelve el alto de la imagen
   * 
   * @access public
   * @return int
   */
  public function getHeight()
  {
    return $this->_height;
  }

  /**
   * Devuelve el tipo de la imagen, el tipo corresponde a las constantes IMAGETYPE_*
   * del nucleo de PHP
   * 
   * @access public
   * @return int
   */
  public function getImageType()
  {
    return $this->_image_type;
  }

  /**
   * Devuelve el tipo MIME de la imagen
   * 
   * @access public
   * @return string
   */
  public function getMimeType()
  {
    return $this->_mime_type;
  }

  /**
   * Redimensiona la imagen al ancho y alto maximo $max_w y $max_h respectivamente,
   * si $output_file_name es null el redimensionado se aplica sobre la misma imagen
   * del objeto, de lo contrario se guarda una copia en $output_file_name, si $crop
   * es true, se ajusta el redimensionado al alto y ancho maximo y se hace un crop
   * central de la imagen, si ocurre un error durante el proceso se lanza una
   * excepcion
   * 
   * @access public
   * @param int $max_w
   * @param int $max_h
   * @throws QuarkImageException
   */
  public function resize($max_w, $max_h, $output_file_name = null, $crop = false)
  {
    switch($this->_image_type){
      case IMAGETYPE_JPEG:
        $img_src = imagecreatefromjpeg($this->_file_path);
        break;
      default:
        throw new QuarkImageException('resize: Tipo de imagen no sportada',
          QuarkImageException::ERR_UNSUPPORTED_TYPE);
        break;
    }

    if($img_src === false){
      throw new QuarkImageException('resize: No se pudo crear la imagen fuente',
        QuarkImageException::ERR_IMAGE_CREATE);
    } else {
      // Calcular las dimensiones de la nueva imagen
      $src_x = 0;
      $src_y = 0;
      $dst_w = 0;
      $dst_h = 0;

      $this->computeResize(
        // Valores de entrada
        $this->_width,
        $this->_height,
        $max_w,
        $max_h,
        $crop,
        // Valores de salida
        $dst_w,
        $dst_h,
        $src_x,
        $src_y
      );

      // Crear la imagen destino
      $img_dst = imagecreatetruecolor($dst_w, $dst_h);

      if($img_dst === false){
        throw new QuarkImageException('resize: No se pudo crear la imagen destino',
          QuarkImageException::ERR_IMAGE_CREATE);
      } else {
        // Copiar el contenido de la imagen fuente a la imagen destino
        $image_copied = imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0,
          $dst_w, $dst_h, $this->_width, $this->_height);

        if($image_copied && $crop){
          $img_crop = imagecreatetruecolor($max_w, $max_h);
          $image_copied = imagecopy(
            $img_crop,
            $img_dst,
            0,
            0,
            $src_x,
            $src_y,
            $max_w,
            $max_h
          );
          imagedestroy($img_dst);
          $img_dst = $img_crop;
        }

        if($image_copied === false){
          throw new QuarkImageException('resize: No se pudo copiar la imagen',
            QuarkImageException::ERR_IMAGE_COPY);
        } else {

          // Guardar la imagen copiada
          if($output_file_name == null){
            // Sobre escribir la imagen fuente
            $output_file_name = $this->_file_path;

            // Al sobre escribir la imagen fuente se cambian sus dimensiones
            if($crop){
              $this->_width  = $max_w;
              $this->_height = $max_h;
            } else {
              $this->_width  = $dst_w;
              $this->_height = $dst_h;
            }
          }

          // Guardar la imagen en un archivo diferente (una copia)
          switch($this->_image_type){
            case IMAGETYPE_JPEG:
              $image_created = imagejpeg($img_dst, $output_file_name,
                $this->_jpg_quality);
              break;
          }

          if($image_created === false){
            throw new QuarkImageException(
              'resize: No se pudo guardar la imagen, verifique los permisos',
              QuarkImageException::ERR_SAVE_IMAGE);
          } else {
            // Devolver el objeto QuarkImage de la imagen redimensionada
            if($this->_file_path == $output_file_name){
              return $this;
            } else {
              return new QuarkImage($output_file_name);
            }
          }
        }
      }
    }
  }

  /**
   * Calcula los valores para redimensionar la imagen y los guarda en los argumentos
   * de salida
   */
  protected function computeResize($src_w, $src_h, $max_w, $max_h, $crop,
    &$out_w, &$out_h, &$src_x, &$src_y)
  {
    $out_w = $max_w;
    $out_h = $max_h;
    $src_x = 0;
    $src_y = 0;

    if($crop){
      /*
       * Definir si el calculo se va a realizar en base al ancho o al alto de
       * la imagen.
       */
      $from_width = false;
      if($max_w == $max_h){
        $from_width = $src_w < $src_h;
      } elseif($max_w > $max_h){
        $from_width = true;
      }

      /*
       * Calcular dimensiones para realizar crop, centrado
       */
      if($from_width){
        // Calcular el nuevo alto en base al nuevo ancho
        $out_h = round(($src_h / $src_w) * $max_w);
      } else {
        // Calcular el nuevo ancho en base al nuevo alto
        $out_w = round( ($src_w / $src_h) * $max_h );
      }

      // Calcular el centro del crop
      $src_x = round( ($out_w/2) - ($max_w/2) );
      $src_y = round( ($out_h/2) - ($max_h/2) );
    } else {

      /*
       * Redimensionado basico
       */
      if($src_w >= $src_h){
        // Calcular el nuevo alto en base al nuevo ancho
        $out_h = round(($src_h / $src_w) * $max_w);
      } else {
        // Calcular el nuevo ancho en base al nuevo alto
        $out_w = round( ($src_w / $src_h) * $max_h );
      }

      // Segundo calculo para evitar que ancho o alto sean mayores a los maximos
      if($out_w > $max_w){
        $out_h = round( ($out_h / $out_w) * $max_w );
        $out_w = $max_w;
      }

      if($out_h > $max_h){
        $out_w = round( ($out_w / $out_h) * $max_h );
        $out_h = $max_h;
      }
    }
  }

  /**
   * Devuelve la cadena HTML necesaria para mostrar la imagen en un documento, el
   * argumento $alt corresponde al atributo "alt" de la imagen, si necesita agregar
   * más atributos al tag <img> se utiliza $extra_attributes
   * 
   * @access public
   * @param string $alt
   * @param string $extra_attributes
   * @return string
   */
  public function toHTML($alt = null, $extra_attributes = null)
  {
    $img_path = QuarkStr::cleanSlashes(str_replace(
      dirname($_SERVER['SCRIPT_FILENAME']), null, $this->_file_path));

    return '<img src="'. $img_path. '" alt="'. QuarkStr::escape($alt). '" '
      . $extra_attributes. ' width="'. $this->_width. '" height="'
      . $this->_height. '"/>';
  }
}
