<?php
/**
 * QuarkPHP Framework
 * Copyright 2012-2013 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

class QuarkDBException extends Exception
{
  private $PDOException = null;

  /**
   * Código al no poder crear una instancia de PDO
   * @var int
   */
  const ERROR_CONNECTION           = 1;
  const ERROR_UNDEFINED_CONNECTION = 2;
  const ERROR_SET_NAMES            = 4;
  const ERROR_COLUMN_NOT_FOUND     = 8;
  const ERROR_BAD_CLASS_NAME       = 16;
  const ERROR_UNDEFINED_FETCH_TYPE = 32;
  const ERROR_NO_QUERY_TYPE        = 64;
  const ERROR_QUERY                = 128;
  const ERROR_MISSING_PROPERTY     = 256;

  public function __construct($message, $code, PDOException $PDOException = null)
  {
    if ($PDOException != null) {
      $message .= ' [PDO Exception Message: '.$PDOException->getMessage().']';
    }
    parent::__construct($message, $code);
    $this->PDOException = $PDOException;
  }

  public function getPDOException()
  {
    return $this->PDOException;
  }
}
