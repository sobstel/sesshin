<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Tests\Session;
use Sesshin\Session\Session;

class SessionTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var Sesshin\Session\Session
   */
  private $session;
  private $id_handler;
  private $storage;
  
  public function setUp() {
    $session = new Session();
    
    $id_handler = $this->getMock('\Sesshin\Id\Handler', array('generateId', 'getId', 'setId', 'issetId', 'unsetId'));    
    $session->setIdHandler($id_handler);
    
    $storage = $this->getMock('\Sesshin\Storage\StorageInterface', array('store', 'fetch', 'delete'));
    $session->setStorage($storage);
    
    $this->session = $session;
    $this->id_handler = $id_handler;
    $this->storage = $storage;
  }
  
  public function testCreateGeneratesId() {
    $this->id_handler->expects($this->once())->method('generateId');    
    $this->session->create();
  }

  public function testCreateUnsetsAllValues() {
    $ref_prop_values = new \ReflectionProperty('\Sesshin\Session\Session', 'values');
    $ref_prop_values->setAccessible(true);
    $ref_prop_values->setValue($this->session, array(1, 2, 3, 4));
    
    $this->session->create();

    $this->assertEmpty($ref_prop_values->getValue($this->session));
  }
}
