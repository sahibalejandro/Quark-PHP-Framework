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
   * @var array
   */
  private $params;

  /**
   * Alias para la columna resultado de esta expresión
   * @var string
   */
  private $alias;

  /**
   * Crea una instancia
   * 
   * @param string $expression Expresión SQL
   * @param array $params Array (key/value) de argumentos para esta expresión
   * @param string $alias Nombre de la columna resultado de esta expresión
   */
  public function __construct($expression, $params = null, $alias = null)
  {
    if ($params == null) {
      $params = array();
    }

    /* Asignar ID a los placeholders de los parametros, para evitar la colisión con
     * otros placeholders de mismo nombre en la misma consulta. */
    if (count($params) > 0) {
      QuarkDBUtils::assignPlaceholdersID($expression, $params);
    }

    $this->expression = $expression;
    $this->params     = $params;
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
   * Devuelve el alias que se usa para el resultado de la expresión cuando se utiliza
   * en la lista de campos de selección.
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
  public function getParams()
  {
    return $this->params;
  }

  /**
   * Alias de getParams()
   * @deprecated Usar getParams() en su lugar, este metodo se removerá en v4
   * @return array
   */
  public function getArguments()
  {
    return $this->getParams();
  }
}
