<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Definición de QuarkImageException
 */
class QuarkImageException extends Exception
{
    const ERR_FILE_NOT_FOUND = 1;
    const ERR_IMAGE_INFO = 2;
    const ERR_INVALID_FORMAT = 3;
    const ERR_IMAGE_CREATE = 4;
    const ERR_RESIZE = 5;
}