<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

class QuarkSQLExpression
{
  /**
   * Expresión SQL
   * @var string
   */
  private $expression;

  /**
   * Array de argumentos (key/value) que serán utilizados por esta expresión
   * @var arguments
   */
  private $arguments;

  /**
   * Alias para la columna resultado de esta expresión
   * @var string
   */
  private $alias;

  /**
   * Crea una instancia
   * 
   * @param string $expression Expresión SQL
   * @param array $arguments Array (key/value) de argumentos para esta expresión
   * @param string $alias Nombre de la columna resultado de esta expresión
   */
  public function __construct($expression, $arguments = null, $alias = null)
  {
    $this->expression = $expression;
    if ($arguments == null) {
      $arguments = array();
    }
    $this->arguments  = $arguments;
    $this->alias      = $alias;
  }

  /**
   * Devuelve el string de la expresión
   * 
   * @return string
   */
  public function getExpression()
  {
    return $this->expression;
  }

  /**
   * Devuelve el alias que se usa para el resultado de la expresión.
   * Nota: Este metodo no es utilizado en QuarkORM Engine.
   * 
   * @return string
   */
  public function getAlias()
  {
    return $this->alias;
  }

  /**
   * Devuelve los argumentos que serán utilizados en esta expresión
   * 
   * @return array
   */
  public function getArguments()
  {
    return $this->arguments;
  }
}
