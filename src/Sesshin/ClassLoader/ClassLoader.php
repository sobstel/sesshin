<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\ClassLoader;

/**
 * Autoloader for classes that can be registered on SPL autoload stack.
 * 
 * Usage:
 * 
 *   $loader = new ClassLoader();
 *   $loader->register();
 */
class ClassLoader {
  
  private $base_path;
  
  public function __construct($base_path = null) {
    if ($base_path === null) {
      $base_path = realpath(__DIR__.'/../..');
    }
    $this->base_path = $base_path;
  }
  
  public function getBasePath() {
    return $this->base_path;
  }

  public function loadClass($classname) {
    $file = $this->getBasePath().'/'.str_replace('\\', '/', $classname).'.php';
    if (file_exists($file)) {
      require_once $file;
    }
  }

  public function register() {
    spl_autoload_register($this->getAutoloadCallback());
  }

  public function getAutoloadCallback() {
    return array($this, 'loadClass');
  }

}
