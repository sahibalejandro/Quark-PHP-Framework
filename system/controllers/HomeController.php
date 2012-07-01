<?php
class HomeController extends QuarkController
{
  public function index()
  {
    echo 'Quark PHP v', QUARK_VERSION, '<br />',
      'Defina su controlador HomeController en application/controllers';
  }
}
