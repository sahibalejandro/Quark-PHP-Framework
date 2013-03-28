<?php
class HomeController extends QuarkController
{
  public function index()
  {
    $P = new Product();
    Quark::dump($P);
  }
}
