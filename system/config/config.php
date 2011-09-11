<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/*
 * Este archivo es de ejemplo, haga una copia de este archivo en su directorio
 * "application/config" y modifique los parametros para ajustarlos a las
 * necesadades de sus sitema
 */

/**
 * Nombre de la sesión, para evitar sobreescribir sesiones de otros sitios
 * basados en quark
 */
$config['session_name'] = 'quark3';

/**
 * Tiempo de vida (en segundos) de la cookie, por default 15 días.
 */
$config['cookie_life_time'] = 1296000;

/**
 * Dominio de la cookie, cuando se van a utilizar sitios en multiples lenguajes y
 * el prefijo de lenguaje se encuentra en el subdominio, es necesario especificar
 * aquí el nombre del dominio al cual pertenecerá la cookie, para que este
 * disponible en todos los subdominios, si no se utiliza especificar NULL
 */
$config['cookie_domain'] = NULL;

/**
 * Si de debug mode es TRUE, se mostraran los detalles de los errores en los
 * mensajes de error.
 * Es responsabilidad del usuario mostrar los mensajes en las vistas
 * personalizadas.
 */
$config['debug'] = TRUE;

/**
 * Lista de prefijos de lenguaje que se utilizaran para un sitio multi lenguaje,
 * el primer lenguaje definido será el lenguaje por default.
 */
$config['langs'] = array();

/**
 * Si lang on subdomain es TRUE, el prefijo del lenguaje se tomará del subdominio
 * y no del path info, esto tambien afectara el comportamiento de los metodos de
 * QuarkUrl para generar las URL validas con el prefijo de lenguaje en el
 * subdominio.
 */
$config['lang_on_subdomain'] = FALSE;

/**
 * Flags para error_reporting()
 */
$config['error_reporting'] = E_ALL ^ E_DEPRECATED;

/**
 * Lista de nombres de clases que serán instanciadas automaticamente y agregadas
 * como propiedades al controller y a la vista relacionada al controller.
 *
 * Los archivos deben tener el mismo nombre que la clase, es decir, para una
 * clase llamada "MyClass", el archivo correspondiente debera ser "MyClass.php" y
 * deberá estar alojados en alguno de los sigientes directorios (con orden de
 * prioridad):
 *
 * "application/classes", "application/models", "application/includes"
 */
$config['auto_instances'] = array();

/**
 * Lista de archivos a incluir antes de instanciar el controlador, por lo general
 * estos archivos son para definir constantes y/o funciones para que esten
 * disponibles desde cualquier punto en la aplicación.
 * Estos archivos deberan estar alojados en el directorio "application/includes"
 */
$config['auto_includes'] = array();

/**
 * Si autoload quark ui es TRUE, se incluiran automaticamente los recursos CSS y
 * JS para utilizar Quark UI en las vistas
 * al utilizar QuarkView::includeCssFiles() y QuarkView::includeJsFiles()
 */
$config['autoload_quarkui'] = FALSE;

/**
 * Lista de directorios (a partir del root de la aplicación) donde __autoload()
 * buscará los archivos de definición de clases, es útil por ejemplo si quieremos
 * organizar controladores en subdirectorios dentro del diectorio
 * "application/controllers"
 */
$config['class_paths'] = array();
