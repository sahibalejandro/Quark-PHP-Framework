<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/*
 * This is the default configuration file used in your site, if you need custom
 * configuration copy this entire file (or just the variables you need) to
 * "application/config/config.php"
 */

/*===================================================================================

  ENVIRONMENT CONFIGURATION
  
  =================================================================================*/

/**
 * Session name, this must be diferent between each of your projects to avoid
 * session data overwriting.
 */
$config['session_name'] = 'quark3';

/**
 * Default time zone for functions like strtotime()
 */
$config['time_zone'] = 'America/Mexico_City';

/**
 * LOCALE CONFIG:
 * The following directives will be used with setlocale()
 * If you don't know what this is you should not touch it.
 *
 * If lc_all is NOT NULL then all other lc_* directives will take the same value
 * of lc_all.
 * 
 * More info en: http://php.net/manual/en/function.setlocale.php
 */
$config['lc_all']      = null;
$config['lc_collate']  = '0';
$config['lc_ctype']    = '0';
$config['lc_monetary'] = '0';
$config['lc_numeric']  = '0';
$config['lc_time']     = array('es_MX.UTF8', 'esm');
// Use only available if PHP was compiled with libintl
// $config['lc_messages'] = "0";

/**
 * Execution will stops if magic quotes GPC is activated, showing an error message.
 * You should never use magic quotes GPC.
 */
$config['error_magic_quotes_gpc'] = true;

/**
 * Cookie lifetime in seconds, default to 15 days.
 */
$config['cookie_life_time'] = 1296000;

/**
 * If you're using multilanguage sites with language defined in subdomain you
 * should set this variable to your main domian, for example, if you're building
 * a web site in languages english and japanese then your domains will be:
 * "eng.mydomain.com" and "jap.mydomain.com" and the value of this variable should
 * be "mydomain.com" to share the cookie across "eng" and "jap" subdomains.
 */
$config['cookie_domain'] = null;

/**
 * When debug is true, all error messages will be displayed, you should set this to
 * false in production environments.
 */
$config['debug'] = true;

/**
 * Language prefixes, if this array is not empty then QUARK_MULTILANG constant will
 * be true (or false otherwise), this elements will be prefix any URL generated
 * with QuarkURL->getURL() or QuarkURL->getBaseURL() methods automatically.
 */
$config['langs'] = array();

/**
 * Defines if the language prefix will be on the subdomain of any URL, or will be
 * as a part of the URL path, for example, if is set to false then the URLs will
 * be like "mydomain.com/eng/home/", if is set to true then the URLs will be
 * like "eng.mydomian.com/home/"
 */
$config['lang_on_subdomain'] = false;

/**
 * error_reporting() flags
 */
$config['error_reporting'] = E_ALL ^ E_DEPRECATED;

/**
 * Scripts that are included before the controller is instantiated, these scripts
 * should be in the "application/includes" path.
 */
$config['auto_includes'] = array();

/**
 * List of paths to use with __autoload()
 * These paths are relative to your project root.
 * "application/classes" is used automatically.
 */
$config['class_paths'] = array();

/*===================================================================================

  DATABASE CONFIGURATION
  
  "default" is the default connection to use, if you need to connect to more than one
  database copy all $db_config directives and change "default" to the name you want
  to that connection.
  
  "default" connection ALWAYS need to be there.
  
  =================================================================================*/
$db_config['default'] = array(
  // Database host name or IP
  'host'     => '127.0.0.1',
  // Database name
  'database' => 'database_name',
  // Database user
  'user'     => 'user_name',
  // Database password
  'password' => 'user_password',
  // Character encoding to use in the "SET NAMES" query
  'charset'  => 'UTF8',
  /* Driver specific options for the PDO object used by the ORM engine.
   * See http://www.php.net/manual/es/pdo.setattribute.php */
  'options'  => array(),
);

/*===================================================================================

  ROUTES CONFIGURATION
  
  =================================================================================*/

/**
 * Asociative array where the key is the regexp to match and the value is the the
 * route to be used.
 * 
 * Example: "home/([0-9]+)" => 'home/entry/$1'
 * Any URL that match "home/<number>" will be trated as "home/entry/<number>"
 */
$routes = array();
