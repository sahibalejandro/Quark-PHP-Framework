<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
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
     * Calidad de salida para los archivos PNG, del 1 al 9
     * @var int
     */
    protected $png_quality;
    
    /**
     * Calidad de salida para los archivos JPG, del 1 al 100
     * @var int
     */
    protected $jpg_quality;
    
    function __construct()
    {
        // Validar la existencia de GD
        if (!function_exists('gd_info')) {
            throw new QuarkImageException('Librería GD no disponible');
        } else {
            $this->png_quality = 9;
            $this->jpg_quality = 90;
        }
    }
    
    /**
     * Redimensiona la imagen $input_file al ancho maximo $max_w y alto maximo $max_h
     * 
     * La imagen redimensionada se guarda en la rulta $output_file
     * 
     * @param string $input_file
     * @param string $output_file
     * @param int $max_w
     * @param int $max_h
     * @throws QuarkImageException
     */
    function resize($input_file, $output_file, $max_w, $max_h)
    {
        // Verificar el archivo original
        if (!is_file($input_file)) {
            throw new QuarkImageException('Archivo de imagen "' . $input_file . '" no existe.', QuarkImageException::ERR_FILE_NOT_FOUND);
        }
        
        // Obtener datos de la imagen original
        $img_info = @getimagesize($input_file);
        if ($img_info == FALSE) {
            throw new QuarkImageException('Error al obtener información del archivo "' . $input_file . '"', QuarkImageException::ERR_IMAGE_INFO);
        }
        
        /*
		 * Calcular las nuevas dimensiones de la imagen final.
		 */
        $img_new_w = $img_src_w = $img_info[0];
        $img_new_h = $img_new_src_h = $img_src_h = $img_info[1];
        
        // Calcular en base al ancho
        if ($img_new_w > $max_w) {
            $img_new_w = $max_w;
            $img_new_src_h = $img_new_h = round($img_new_h / ($img_src_w / $img_new_w));
        }
        
        // Calcular en base al alto
        if ($img_new_h > $max_h) {
            $img_new_h = $max_h;
            $img_new_w = round($img_new_w / ($img_new_src_h / $img_new_h));
        }
        
        // Realizar redimensionado solo si es necesario
        if ($img_new_w != $img_info[0] || $img_new_h != $img_info[1]) {
            
            /*
			 * Leer imagen original
			 */
            switch ($img_info[2]) {
                case IMAGETYPE_GIF:
                    $img_src = @imagecreatefromgif($input_file);
                    break;
                case IMAGETYPE_PNG:
                    $img_src = @imagecreatefrompng($input_file);
                    break;
                case IMAGETYPE_JPEG:
                    $img_src = @imagecreatefromjpeg($input_file);
                    break;
                default :
                    throw new QuarkImageException('El formato de imagen de "' . $input_file . '" no es válido.', QuarkImageException::ERR_INVALID_FORMAT);
                    break;
            }
            
            if ($img_src == FALSE) {
                throw new QuarkImageException('Error al crear la imagen desde el archivo original', QuarkImageException::ERR_IMAGE_CREATE);
            }
            
            /*
			 * Crear imagen destino
			 */
            $img_dst = @imagecreatetruecolor($img_new_w, $img_new_h);
            
            if ($img_dst == FALSE) {
                throw new QuarkImageException('Error al crear la imagen destino', QuarkImageException::ERR_IMAGE_CREATE);
            }
            
            /*
			 * Copiar mapa de bits de la imagen original en la imagen destino
			 * con las proporciones calculadas. 
			 */
            $copy_result = @imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0, $img_new_w, $img_new_h, $img_info[0], $img_info[1]);
            
            if ($copy_result == FALSE) {
                @imagedestroy($img_dst);
                throw new QuarkImageException('Error al redimensionar la imagen', QuarkImageException::ERR_RESIZE);
            }
            
            /*
			 * Guardar archivo de imagen final
			 */
            switch ($img_info[2]) {
                case IMAGETYPE_GIF:
                    $output_result = @imagegif($img_dst, $output_file);
                    break;
                case IMAGETYPE_PNG:
                    $output_result = @imagepng($img_dst, $output_file, $this->png_quality);
                    break;
                case IMAGETYPE_JPEG:
                    $output_result = @imagejpeg($img_dst, $output_file, $this->jpg_quality);
                    break;
            }
            
            // Liberar memoria
            @imagedestroy($img_src);
            @imagedestroy($img_dst);
            
            if ($output_result == FALSE) {
                throw new QuarkImageException('Error al crear la imagen final', QuarkImageException::ERR_IMAGE_CREATE);
            }
        
        } // if( $img_new_w != $img_info[0] || $img_new_h != $img_info[1] )
    }
    
    /**
     * Corta una imagen
     * 
     * @param string $input_file
     * @param string $output_file
     * @param int $width
     * @param int $height
     * @param bool $center
     * @throws QuarkImageException
     */
    public function crop($input_file, $output_file, $width, $height, $center = TRUE)
    {
        /*
		 * Primero validar el File System
		 */
        if (!is_file($input_file))
            throw new QuarkImageException("El archivo de imagen $input_file no existe.", QuarkImageException::ERR_FILE_NOT_FOUND);
            
        /*
		 * Obtener datos de la imagen original y calcular el cortado
		 */
        $img_src_info = getimagesize($input_file);
        $src_x = 0;
        $src_y = 0;
        
        if ($center) {
            $src_x = floor(($img_src_info[0] / 2) - ($width / 2));
            $src_y = floor(($img_src_info[1] / 2) - ($height / 2));
        }
        
        /*
		 * Crear buffer de imagenes
		 */
        $img_dst = @imagecreatetruecolor($width, $height);
        
        /*
		 * Leer imagen original
		 */
        switch ($img_src_info[2]) {
            case IMAGETYPE_GIF:
                $img_src = @imagecreatefromgif($input_file);
                break;
            case IMAGETYPE_PNG:
                $img_src = @imagecreatefrompng($input_file);
                break;
            case IMAGETYPE_JPEG:
                $img_src = @imagecreatefromjpeg($input_file);
                break;
            default :
                throw new QuarkImageException('El formato de imagen de "' . $input_file . '" no es válido.', QuarkImageException::ERR_INVALID_FORMAT);
                break;
        }
        
        if ($img_src == FALSE) {
            throw new QuarkImageException('Error al crear la imagen desde el archivo original.', QuarkImageException::ERR_IMAGE_CREATE);
        }
        
        @imagecopy($img_dst, $img_src, 0, 0, $src_x, $src_y, $width, $height);
        
        /*
		 * Guardar archivo de imagen final
		 */
        switch ($img_src_info[2]) {
            case IMAGETYPE_GIF:
                $output_result = @imagegif($img_dst, $output_file);
                break;
            case IMAGETYPE_PNG:
                $output_result = @imagepng($img_dst, $output_file, $this->png_quality);
                break;
            case IMAGETYPE_JPEG:
                $output_result = @imagejpeg($img_dst, $output_file, $this->jpg_quality);
                break;
        }
        
        // Liberar memoria
        @imagedestroy($img_src);
        @imagedestroy($img_dst);
        
        if ($output_result == FALSE) {
            throw new QuarkImageException('Error al crear la imagen final', QuarkImageException::ERR_IMAGE_CREATE);
        }
    }
    
    /**
     * Devuelve la calidad de salida de las imagenes PNG
     * @return int
     */
    public function getPngQuality()
    {
        return $this->png_quality;
    }
    
    /**
     * Establece la calidad de salida de las imagenes PNG
     * @param int $png_quality Entero del 1 al 9
     */
    public function setPngQuality($png_quality)
    {
        $this->png_quality = $png_quality;
    }
    
    /**
     * Devuelve la calidad de salida de las imagenes JPG
     * @return int
     */
    public function getJpgQuality()
    {
        return $this->jpg_quality;
    }
    
    /**
     * Establece la calidad de salida de las imagenes JPG
     * @param int $jpg_quality Entero del 1 al 100
     */
    public function setJpgQuality($jpg_quality)
    {
        $this->jpg_quality = $jpg_quality;
    }
}