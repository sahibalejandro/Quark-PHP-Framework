<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

class QuarkImageException extends Exception
{
  /**
   * Error al obtener informacion de la imagen
   * 
   * @access public
   * @var int
   */
  const ERR_IMAGE_INFO = 1;

  /**
   * Tipo de imagen no soportado
   * 
   * @access public
   * @var int
   */
  const ERR_UNSUPPORTED_TYPE = 2;

  /**
   * No se pudo crear una imagen con imagecreate*
   * 
   * @access public
   * @var int
   */
  const ERR_IMAGE_CREATE = 4;

  /**
   * No se pudo copiar la imagen con imagecopy*
   * 
   * @access public
   * @var int
   */
  const ERR_IMAGE_COPY = 8;

  /**
   * No se pudo guardar el archivo de imagen
   * 
   * @access public
   * @var int
   */
  const ERR_SAVE_IMAGE = 16;
}
