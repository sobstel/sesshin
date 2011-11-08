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
use Sesshin\Tests\TestCase;
use Sesshin\Session\Session;

class SessionTest extends TestCase {

  /**
   * @var Sesshin\Session\Session
   */
  private function setUpDefaultSession($session = null) {
    if (is_null($session)) {
      $session = new Session();
    }

    $id_handler = $this->getMock('\Sesshin\Id\Handler', array('generateId', 'getId', 'setId', 'issetId', 'unsetId'));
    $session->setIdHandler($id_handler);

    $storage = $this->getMock('\Sesshin\Storage\StorageInterface', array('store', 'fetch', 'delete'));
    $session->setStorage($storage);

    $listener = $this->getMock('\Sesshin\Listener\Listener', array('trigger', 'bind', 'getQueue'));
    $session->setListener($listener);

    return $session;
  }

  /**
   * @covers Sesshin\Session\Session::setValue
   */
  public function testValueIsSetToDefaultNamespaceByDefault() {
    $session = $this->setUpDefaultSession();
    $ref_prop = $this->setPropertyAccessible($session, 'values');

    $session->setValue('name', 'value');

    $values = $ref_prop->getValue($session);
    $this->assertEquals('value', $values[Session::DEFAULT_NAMESPACE]['name']);
  }

  /**
   * @covers Sesshin\Session\Session::setValue
   */
  public function testCanSetValueToCustomNamespace() {
    $session = $this->setUpDefaultSession();
    $ref_prop = $this->setPropertyAccessible($session, 'values');

    $session->setValue('name', 'value', 'namespace');

    $values = $ref_prop->getValue($session);
    $this->assertEquals('value', $values['namespace']['name']);
  }

  /**
   * @covers Sesshin\Session\Session::getValue
   * @depends testValueIsSetToDefaultNamespaceByDefault
   */
  public function testCanGetValue() {
    $session = $this->setUpDefaultSession();
    $session->setValue('name', 'value');
    $this->assertSame('value', $session->getValue('name'));
  }

  /**
   * @covers Sesshin\Session\Session::getValue
   * @depends testValueIsSetToDefaultNamespaceByDefault
   */
  public function testCanGetValueMethodReturnsNullIfNoValueForGivenName() {
    $session = $this->setUpDefaultSession();
    $this->assertNull($session->getValue('name'));
  }

  /**
   * @covers Sesshin\Session\Session::issetValue
   * @depends testValueIsSetToDefaultNamespaceByDefault
   */
  public function testCanCheckIfValueIsSet() {
    $session = $this->setUpDefaultSession();
    $session->setValue('name', 'value');
    $this->assertTrue($session->issetValue('name'));
  }

  /**
   * @covers Sesshin\Session\Session::unsetValue
   * @depends testValueIsSetToDefaultNamespaceByDefault
   */
  public function testCanUnsetValues() {
    $session = $this->setUpDefaultSession();
    $session->setValue('name', 'value');
    $session->unsetValue('name');
    $this->assertNull($session->getValue('name'));
  }

  /**
   * @covers Sesshin\Session\Session::getUnsetValue
   */
  public function testCanGetAndUnsetValue() {
    $session = $this->setUpDefaultSession();
    $session->setValue('name', 'value');
    $value = $session->getUnsetValue('name');
    $this->assertEquals('value', $value);
    $this->assertNull($session->getValue('name'));
  }

  /**
   * @covers Sesshin\Session\Session::getValues
   */
  public function testCanGetAllValuesForNamespace() {
    $session = $this->setUpDefaultSession();
    $session->setValue('name1', 'value1');
    $session->setValue('name2', 'value2');
    $this->assertEquals(array('name1' => 'value1', 'name2' => 'value2'), $session->getValues());
  }

  /**
   * @covers Sesshin\Session\Session::unsetValues
   */
  public function testCanUnsetAllValuesForNamespace() {
    $session = $this->setUpDefaultSession();
    $session->setValue('name1', 'value1');
    $session->setValue('name2', 'value2');
    $session->unsetValues();
    $this->assertNull($session->getValue('name1'));
    $this->assertNull($session->getValue('name2'));
    $this->assertEmpty($session->getValues());
  }

  /**
   * @covers Sesshin\Session\Session::getRequestsCounter
   */
  public function testCanGetRequestsCounter() {
    $session = $this->setUpDefaultSession();
    $value = 37;
    $this->setPropertyValue($session, 'requests_counter', $value);
    $this->assertEquals($value, $session->getRequestsCounter());
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodGeneratesId() {
    $session = $this->setUpDefaultSession();
    $session->getIdHandler()->expects($this->once())->method('generateId');
    $session->create();
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodUnsetsAllValues() {
    $session = $this->setUpDefaultSession();
    $ref_prop_values = $this->setPropertyValue($session, 'values', array(1, 2, 3, 4));
    $session->create();
    $this->assertEmpty($ref_prop_values->getValue($session));
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodResetsFirstTrace() {
    $session = $this->setUpDefaultSession();
    $first_trace = $session->getFirstTrace();
    $session->create();
    $this->assertNotEquals($first_trace, $session->getFirstTrace());
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodResetsLastTrace() {
    $session = $this->setUpDefaultSession();
    $last_trace = $session->getLastTrace();
    $session->create();
    $this->assertNotEquals($last_trace, $session->getLastTrace());
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodResetsRequestsCounter() {
    $session = $this->setUpDefaultSession();
    $session->create();
    $this->assertEquals(1, $session->getRequestsCounter());
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodResetsIdRegenerationTrace() {
    $session = $this->setUpDefaultSession();
    $regeneration_trace = $session->getRegenerationTrace();
    $session->create();
    $this->assertNotEquals($regeneration_trace, $session->getRegenerationTrace());

    $value = 1;
    $session = $this->setUpDefaultSession();
    $this->setPropertyValue($session, 'regeneration_trace', $value);
    $session->create();
    $this->assertNotEquals($value, $session->getRegenerationTrace());

    $this->assertGreaterThanOrEqual(time() - 1, $session->getRegenerationTrace());
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodGeneratesFingerprint() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('generateFingerprint')));
    $session->expects($this->once())->method('generateFingerprint');
    $session->create();
  }

  /**
   * @covers Sesshin\Session\Session::create
   */
  public function testCreateMethodOpensSession() {
    $session = $this->setUpDefaultSession();
    $session->create();
    $this->assertEquals(true, $session->isOpened());
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodWhenCalledWithTrueThenCreatesNewSessionIfSessionNotExistsAlready() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create')));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(false));
    $session->expects($this->once())->method('create');

    $session->open(true);
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodWhenCalledWithTrueThenDoesNotCreateNewSessionIfSessionIdExistsAlready() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create')));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(true));
    $session->expects($this->never())->method('create');

    $session->open(true);
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodWhenCalledWithFalseThenDoesNotCreateNewSession() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create')));
    $session->expects($this->never())->method('create');

    $session->open(false);
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodLoadsSessionDataIfSessionExists() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create', 'load')));
    $session->expects($this->any())->method('isOpened')->will($this->returnValue(false));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(true));
    $session->expects($this->once())->method('load');

    $session->open();
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodDoesNotLoadSessionDataIfSessionNotExists() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create', 'load')));
    $session->expects($this->any())->method('isOpened')->will($this->returnValue(false));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(false));
    $session->expects($this->never())->method('load');

    $session->open();
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodTriggersSessionNoDataOrExpiredEventIfNoDataPresentAfterLoad() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create', 'load', 'getFirstTrace')));
    $session->expects($this->any())->method('isOpened')->will($this->returnValue(false));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(true));
    $session->expects($this->once())->method('getFirstTrace')->will($this->returnValue(false));
    $session->getListener()->expects($this->once())->method('trigger')->with($this->equalTo(Session::EVENT_NO_DATA_OR_EXPIRED));

    $session->open();
  }

  /**
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodTriggersSessionExpiredEventIfSessionExpired() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create', 'load', 'getFirstTrace', 'isExpired')));
    $session->expects($this->any())->method('isOpened')->will($this->returnValue(false));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(true));
    $session->expects($this->once())->method('getFirstTrace')->will($this->returnValue(time()));
    $session->expects($this->once())->method('isExpired')->will($this->returnValue(true));
    $session->getListener()->expects($this->once())->method('trigger')->with($this->equalTo(Session::EVENT_EXPIRED));

    $session->open();
  }

  /**
   * Fingerpring is generated, so it can be compared with the one in session
   * metadata for session validity.
   *
   * @covers Sesshin\Session\Session::open
   */
  public function testOpenMethodTriggersInvalidFingerprintEventIfLoadedFingerprintInvalid() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create', 'load', 'getFirstTrace', 'isExpired', 'getFingerprint', 'generateFingerprint')));
    $session->expects($this->any())->method('isOpened')->will($this->returnValue(false));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(true));
    $session->expects($this->once())->method('getFirstTrace')->will($this->returnValue(time()));
    $session->expects($this->once())->method('isExpired')->will($this->returnValue(false));
    $session->expects($this->once())->method('getFingerprint')->will($this->returnValue('abc'));
    $session->expects($this->once())->method('generateFingerprint')->will($this->returnValue('def'));
    $session->getListener()->expects($this->once())->method('trigger')->with($this->equalTo(Session::EVENT_INVALID_FINGERPRINT));

    $session->open();
  }

  /**
   * @covers Sesshin\Session\Session::open
   * @depends testCanGetRequestsCounter
   */
  public function testOpenMethodOpenSessionAndIncrementsRequestsCounter() {
    $session = $this->setUpDefaultSession($this->getMock('\Sesshin\Session\Session', array('create', 'load', 'getFirstTrace', 'isExpired', 'getFingerprint', 'generateFingerprint')));
    $session->expects($this->any())->method('isOpened')->will($this->returnValue(false));
    $session->getIdHandler()->expects($this->any())->method('issetId')->will($this->returnValue(true));
    $session->expects($this->once())->method('getFirstTrace')->will($this->returnValue(time()));
    $session->expects($this->once())->method('isExpired')->will($this->returnValue(false));
    $session->expects($this->once())->method('getFingerprint')->will($this->returnValue('abc'));
    $session->expects($this->once())->method('generateFingerprint')->will($this->returnValue('abc'));

    $requests_counter = $session->getRequestsCounter();

    $session->open();

    $this->assertSame(true, $session->isOpened());
    $this->assertEquals($requests_counter + 1, $session->getRequestsCounter());
  }

  /**
   * @covers Sesshin\Session\Session::isOpen
   * @covers Sesshin\Session\Session::isOpened
   */
  public function testCanCheckIfSessionIsOpened() {
    $session = $this->setUpDefaultSession();
    $this->setPropertyValue($session, 'opened', true);
    $this->assertSame(true, $session->isOpen());
    $this->assertSame(true, $session->isOpened());
    $this->setPropertyValue($session, 'opened', false);
    $this->assertSame(false, $session->isOpen());
    $this->assertSame(false, $session->isOpened());
  }

  /**
   * @covers Sesshin\Session\Session::setIdHandler
   * @covers Sesshin\Session\Session::getIdHandler
   */
  public function testCanSetGetIdHandler() {
    $session = new Session();
    $id_handler = new \Sesshin\Id\Handler();
    $session->setIdHandler($id_handler);
    $this->assertSame($id_handler, $session->getIdHandler());
  }

  /**
   * @covers Sesshin\Session\Session::getIdHandler
   */
  public function testUsesDefaultIdHandlerIfNotSet() {
    $session = new Session();
    $this->assertEquals('Sesshin\Id\Handler', get_class($session->getIdHandler()));
  }

  /**
   * @covers Sesshin\Session\Session::setStorage
   * @covers Sesshin\Session\Session::getStorage
   */
  public function testCanSetGetStorage() {
    $session = new Session();
    $storage = new \Sesshin\Storage\Files();
    $session->setStorage($storage);
    $this->assertSame($storage, $session->getStorage());
  }

  /**
   * @covers Sesshin\Session\Session::getStorage
   */
  public function testUsesFilesStorageIfNotSet() {
    $session = new Session();
    $this->assertEquals('Sesshin\Storage\Files', get_class($session->getStorage()));
  }

  /**
   * @covers Sesshin\Session\Session::setListener
   * @covers Sesshin\Session\Session::getListener
   */
  public function testCanSetGetListener() {
    $session = new Session();
    $listener = new \Sesshin\Listener\Listener();
    $session->setListener($listener);
    $this->assertSame($listener, $session->getListener());
  }

  /**
   * @covers Sesshin\Session\Session::getListener
   */
  public function testUsesDefaultListenerIfNotSet() {
    $session = new Session();
    $this->assertEquals('Sesshin\Listener\Listener', get_class($session->getListener()));
  }

  /**
   * @covers Sesshin\Session\Session::offsetSet
   * @covers Sesshin\Session\Session::offsetGet
   * @covers Sesshin\Session\Session::offsetExists
   * @covers Sesshin\Session\Session::offsetUnset
   */
  public function testImplementsArrayAccessForSessionValues() {
    $session = $this->getMock('\Sesshin\Session\Session', array('setValue'));
    $session->expects($this->once())->method('setValue')->with($this->equalTo('key'), $this->equalTo('value'));
    $session['key'] = 'value';

    $session = $this->getMock('\Sesshin\Session\Session', array('getValue'));
    $session->expects($this->once())->method('getValue')->with($this->equalTo('key'));
    $session['key'];

    $session = $this->getMock('\Sesshin\Session\Session', array('issetValue'));
    $session->expects($this->once())->method('issetValue')->with($this->equalTo('key'));
    isset($session['key']);

    $session = $this->getMock('\Sesshin\Session\Session', array('unsetValue'));
    $session->expects($this->once())->method('unsetValue')->with($this->equalTo('key'));
    unset($session['key']);
  }

  /**
   * @covers Sesshin\Session\Session::load
   * @depends testOpenMethodLoadsSessionDataIfSessionExists
   */
  public function testLoadMethodFetchesDataFromStorage() {
    $session = $this->setUpDefaultSession();
    $session->getStorage()->expects($this->any())->method('fetch')->with($this->equalTo($session->getId()));    
    $this->invokeMethod($session, 'load');
  }

  /**
   * @covers Sesshin\Session\Session::load
   */
  public function testLoadMethodReturnsFalseIfNoDataInStorage() {
    $session = $this->setUpDefaultSession();
    $session->getStorage()->expects($this->any())->method('fetch')->will($this->returnValue(false));
    $this->assertFalse($this->invokeMethod($session, 'load'));
  }

}
