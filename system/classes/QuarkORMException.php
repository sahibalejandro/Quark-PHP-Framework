<?php
class QuarkORMException extends Exception
{
  const ERR_NO_CONNECTION_INFO = 1;
  const ERR_PDO_EXCEPTION = 2;
  const ERR_NO_QUERY_BUILDER = 4;
  const ERR_NEW_PARENT = 8;
  const ERR_ORM_NOT_FOUND = 16;

  /**
   * Copia del errorInfo del PDOException
   *
   * @access private
   * @var array
   */
  private $_PDOException;

  /**
   * Constructor
   *
   * @access public
   * @var string $message
   * @var int $code
   * @var PDOException $PDOException
   */
  public function __construct($message, $code, PDOException $PDOException = null)
  {
    $this->_PDOException = $PDOException;
    parent::__construct($message, $code);
  }

  public function getPDOException()
  {
    return $this->_PDOException;
  }
}