<?php
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