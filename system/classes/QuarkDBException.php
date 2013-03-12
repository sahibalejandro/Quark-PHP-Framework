<?php
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
