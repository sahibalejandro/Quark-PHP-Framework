<?php
/**
 * Quark 3.5 PHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

class QuarkSQLExpression
{
  private $_expression;
  private $_arguments;

  public function __construct($expression, $arguments = array())
  {
    $this->_expression = $expression;
    $this->_arguments = $arguments;
  }

  public function getExpression()
  {
    return $this->_expression;
  }

  public function getArguments()
  {
    return $this->_arguments;
  }
}
