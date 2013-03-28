<?php
class ExampleORM extends QuarkORM
{
  public static $table      = 'table';
  public static $connection = 'default';

  protected function validate()
  {
    return true;
  }

  public static function query()
  {
    return new QuarkORMQueryBuilder(__CLASS__);
  }
}
