<?php
/**
 * QuarkPHP Framework
 * Copyright 2012-2013 Sahib Alejandro Jaramillo Leo
 *
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

class HomeController extends QuarkController
{
  public function index()
  {
    echo 'Quark PHP v', Quark::VERSION, '<br />',
      'Define your own HomeController in application/controllers';
  }
}
