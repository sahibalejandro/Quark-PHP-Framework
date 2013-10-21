<?php
class QuarkImage_dev
{
  private $img;
  private $img_type;
  private $return_mode;

  const RETURN_MODE_NEW  = 1;
  const RETURN_MODE_SAME = 2;

  /**
   * Construye un nuevo objeto, el usuario debería llamar a QuarkImage_dev::loadImage()
   * en lugar de este contructor.
   */
  public function __construct($img, $img_type, $return_mode)
  {
    $this->img         = $img;
    $this->img_type    = $img_type;
    $this->return_mode = $return_mode;
  }

  /**
   * Liberar memoria.
   */
  public function __destruct()
  {
    if ($this->img !== null) {
      imagedestroy($this->img);
    }
  }

  /**
   * Redimensiona la imagen a las dimensiones maximas establecidas por $width y $height
   * Si la imagen original es más pequeña que las dimensiones máximas no se realizan
   * cambios, con excepción de que $stretch sea TRUE
   * 
   * @param int $width Ancho máximo
   * @param int $height Alto máximo
   * @param bool $stretch Estirar en caso de que la imagen original sea más pequeña
   * @return QuarkImage_dev
   */
  public function resize($width, $height, $stretch = true)
  {
    $actual_w = imagesx($this->img);
    $actual_h = imagesy($this->img);

    list($new_w, $new_h) = self::calculateSize(
      $actual_w, $actual_h,
      $width, $height,
      $stretch
    );

    $new_img = imagecreatetruecolor($new_w, $new_h);
    
    self::copyImage(
      $this->img_type, $this->img, $new_img,
      0, 0,
      $actual_w, $actual_h,
      0, 0,
      $new_w, $new_h
    );

    // Devolver el resultado según el "return mode" establecido.
    if ($this->return_mode == self::RETURN_MODE_NEW) {
      return new QuarkImage_dev($new_img, $this->img_type, $this->return_mode);
    } else {
      $this->img = $new_img;
      return $this;
    }
  }

  /**
   * Recorta una imagen
   * 
   * @param int $width Ancho del corte
   * @param int $height Alto del corte
   * @param string $x_position Posicion X del corte (left, center, right)
   * @param string $y_position Posicion Y del corte (top, center, bottom)
   * @return QuarkImage_dev
   */
  public function crop($width, $height, $x_position = 'left', $y_position = 'top')
  {
    $src_w = imagesx($this->img);
    $src_h = imagesy($this->img);
    list($crop_w, $crop_h) = self::calculateSize(
      $width, $height,
      $src_w, $src_h,
      true
    );

    // Calcular las posiciones del corte
    $src_x = 0;
    $src_y = 0;
    
    switch ($x_position) {
      case 'center':
        $src_x = round(($src_w / 2) - ($crop_w / 2));
        break;
      case 'right':
        $src_x = $src_w - $crop_w;
        break;
    }
    
    switch ($y_position) {
      case 'center':
        $src_y = round(($src_h / 2) - ($crop_h / 2));
        break;
      case 'bottom':
        $src_y = $src_h - $crop_h;
        break;
    }

    $new_img = imagecreatetruecolor($width, $height);
    
    self::copyImage(
      $this->img_type, $this->img, $new_img,
      $src_x, $src_y,
      $crop_w, $crop_h,
      0, 0,
      $width, $height
    );

    // Devolver el resultado según el "return mode" establecido.
    if ($this->return_mode == self::RETURN_MODE_NEW) {
      return new QuarkImage_dev($new_img, $this->img_type, $this->return_mode);
    } else {
      $this->img = $new_img;
      return $this;
    }
  }

  /**
   * Copia una porcion de una imagen en otra, conservando la transparencia
   * si la imagen original es PNG o GIF, devuelve un recurso de imagen copiada.
   * 
   * @param int $src_image_type Tipo de imagen original
   * @param resource $src_image Imagen original (la que se va a copiar)
   * @param resource $dst_image Imagen final (donde se va a pegar)
   * @param int $src_x Coordenada X de la imagen original
   * @param int $src_y Coordenada Y de la imagen original
   * @param int $src_w Ancho de la imagen original
   * @param int $src_h Ancho de la imagen original
   * @param int $dst_x Coordenada X de la imagen destino
   * @param int $dst_y Coordenada Y de la imagen destino
   * @param int $dst_w Ancho de la imagen destino
   * @param int $dst_h Ancho de la imagen destino
   */
  public static function copyImage(
    $src_image_type,
    $src_image,
    $dst_image,
    $src_x,
    $src_y,
    $src_w,
    $src_h,
    $dst_x,
    $dst_y,
    $dst_w,
    $dst_h
  ) {
    
    // Un fondo blanco en general
    imagefill($dst_image, 0, 0, imagecolorallocate($dst_image, 255, 255, 255));

    // Conservar la transparencia para PNG y GIF
    if ($src_image_type == IMAGETYPE_PNG || $src_image_type == IMAGETYPE_GIF) {
      $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
      imagealphablending($dst_image, false);
      imagesavealpha($dst_image, true);
      imagecolortransparent($dst_image, $transparent);
      imagefilledrectangle($dst_image, 0, 0, $dst_w, $dst_h, $transparent);
    }

    /* Para preservar la transparencia en GIF se necesita utilizar imagecopyresized()
     * en lugar de imagecopyresampled() */
    if ($src_image_type == IMAGETYPE_GIF) {
      imagecopyresized(
        $dst_image, $src_image,
        $dst_x, $dst_y,
        $src_x, $src_y,
        $dst_w, $dst_h,
        $src_w, $src_h
      );
    } else {
      imagecopyresampled(
        $dst_image, $src_image,
        $dst_x, $dst_y,
        $src_x, $src_y,
        $dst_w, $dst_h,
        $src_w, $src_h
      );
    }
  }

  /**
   * Calcula el redimensionado de tamaños y devuelve el nuevo ancho y alto
   * 
   * @param int $old_w Ancho actual
   * @param int $old_h Alto actual
   * @param int $max_w Ancho máximo
   * @param int $max_h Alto máximo
   * @param bool $stretch Si es TRUE las dimensiones actuales se "agrandan" para
   *                      cumplir con las dimensiones máximas.
   * @return array(new_width, new_height)
   */
  private static function calculateSize(
    $old_w,
    $old_h,
    $max_w,
    $max_h,
    $stretch = false
  ) {

    if ($max_w === null) {
      // Obtener la escala en base al alto
      $scale = $max_h / $old_h;
    } elseif ($max_h === null) {
      // Obtener la escala en base al ancho
      $scale = $max_w / $old_w;
    } else {
      // Obtener la menor escala
      $scale = min($max_w / $old_w, $max_h / $old_h);
    }

    // Ajustar la escala a 1 (uno) cuando no se quiere hacer estiramiento
    if (!$stretch && $scale > 1) {
      $scale = 1;
    }
    
    // Calcular el nuevo alto, alto y devolver el resultado
    return array($old_w * $scale, $old_h * $scale);
  }

  /**
   * Da la salida a la imagen procesada.
   * 
   * @param string $file Nombre de archivo de salida, si es null se envía al buffer
   * @param int $quality Calidad de salida, para PNG o JPEG
   */
  public function output($file = null, $quality = null)
  {
    // Enviar los headers necesarios si $file es null
    if ($file === null) {
      $mime_type = image_type_to_mime_type($this->img_type);
      header('Content-Type:'.$mime_type);
    }

    // Dar salida al flujo de la imagen en el formato correcto
    switch ($this->img_type) {
      case IMAGETYPE_JPEG:
      case IMAGETYPE_JPEG2000:
        // Si no se especifica calidad, se usa una por defecto
        $quality = $quality === null ? 80 : $quality;
        imagejpeg($this->img, $file, $quality);
        break;
      case IMAGETYPE_PNG:
        // Si no se especifica calidad, se usa una por defecto
        $quality = $quality === null ? 9 : $quality;
        imagepng($this->img, $file, $quality);
        break;
      case IMAGETYPE_GIF:
        imagegif($this->img, $file);
        break;
      default:
        throw new QuarkImageException_dev(
          __METHOD__.': Unsupported image type.',
          QuarkImageException_dev::UNSUPPORTED_IMAGE_TYPE
        );
        break;
    }
  }

  /**
   * Devuelve una nueva instancia de QuarkImage_dev utilizando la imagen $image_file
   * 
   * @param string $image_file Ruta del archivo
   * @return QuarkImage_dev
   */
  public static function loadImage($image_file, $return_mode = QuarkImage_dev::RETURN_MODE_NEW)
  {
    if (!is_file($image_file)) {
      throw new QuarkImageException_dev(
        __METHOD__.': Image file "'.$image_file.'" not found.',
        QuarkImageException_dev::IMAGE_FILE_NOT_FOUND
      );
    } else {
      $image_size = getimagesize($image_file);
      return new QuarkImage_dev(
        imagecreatefromstring(file_get_contents($image_file)),
        $image_size[2],
        $return_mode
      );
    }
  }

  /**
   * Cambia el formato de imagen
   * 
   * @param int $image_type Tipo de la imagen (cualquier constante IMAGETYPE_XXX de PHP)
   * @return QuarkImage_dev
   */
  public function convertTo($image_type)
  {
    $actual_w       = imagesx($this->img);
    $actual_h       = imagesy($this->img);

    $new_img = imagecreatetruecolor($actual_w, $actual_h);
    
    self::copyImage(
      $image_type, $this->img, $new_img,
      0, 0, $actual_w, $actual_h,
      0, 0, $actual_w, $actual_h
    );

    // Devolver el resultado según el "return mode" establecido.
    if ($this->return_mode == self::RETURN_MODE_NEW) {
      return new QuarkImage_dev($new_img, $image_type, $this->return_mode);
    } else {
      $this->img_type = $image_type;
      $this->img      = $new_img;
      return $this;
    }
  }
}
