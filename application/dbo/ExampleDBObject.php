<?php
class ExampleDBObject extends QuarkDBObject
{
  const TABLE      = 'table';
  const CONNECTION = 'default';

  protected function validate()
  {
    return true;
  }

  public static function query()
  {
    return new QuarkDBQuery(__CLASS__);
  }
}
