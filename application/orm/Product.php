<?php
class Product extends QuarkDBObject
{
  const TABLE      = 'products';
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
