<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Tests\ClassLoader;
use Sesshin\ClassLoader\ClassLoader;

class ClassLoaderTest extends \PHPUnit_Framework_TestCase {
  
  public function getClasses() {
    return array(
      array('Sesshin\EntropyGenerator\EntropyGeneratorInterface'),
      array('Sesshin\EntropyGenerator\File'),
      array('Sesshin\EntropyGenerator\Uniq'),
      array('Sesshin\FingerprintGenerator\FingerprintGeneratorInterface'),
      array('Sesshin\FingerprintGenerator\UserAgent'),
      array('Sesshin\Id\Handler'),
      array('Sesshin\Id\Storage\StorageInterface'),
      array('Sesshin\Id\Storage\Cookie'),
      array('Sesshin\Listener\Listener'),
      array('Sesshin\Listener\SplPriorityQueue'),
      array('Sesshin\Session\Session'),
      array('Sesshin\Storage\StorageInterface'),
      array('Sesshin\Storage\Storage'),
      array('Sesshin\Storage\Apc'),
      array('Sesshin\Storage\Files'),
      array('Sesshin\Storage\Memcache'),
      array('Sesshin\User\Session'),
      array('Sesshin\Exception'),
    );
  }
  
  /**
   * @dataProvider getClasses
   */
  public function testClassLoaded($classname) {
    $class_loader = new ClassLoader();
    $class_loader->loadClass($classname);
    $this->assertTrue(class_exists($classname) || interface_exists($classname));
  }
  
}
