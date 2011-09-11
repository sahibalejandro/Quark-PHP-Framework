<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/*
 * Este archivo es de ejemplo, copie este archivo a su directorio
 * "application/config" y modifique los parametros para ajustarlos a las
 * necesadades de sus sitema
 */

/*
 * Lista de rutas, array asociativo, el key es la expresión a evaluar y el valor
 * es la ruta que será reemplazada.
 * 
 * Ej. "home/([0-9]+)" => 'home/entry/$1'
 * Explicación:
 * Toda url que coincida con "home/<cualquier-numero>" sera redireccionada a
 * "home/entry/<numero>"
 */
$routes = array();
