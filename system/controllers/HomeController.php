<?php
/**
 * QuarkPHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

class HomeController extends QuarkController
{
  public function index()
  {
    echo 'Quark PHP v', QUARK_VERSION, '<br />',
      'Define your own HomeController in application/controllers';
  }
}
