<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Session;
use Sesshin\Exception;
use Sesshin\FingerprintGenerator;
use Sesshin\Id;
use Sesshin\Listener;
use Sesshin\Storage;

class Session implements ArrayAccess {

  const DEFAULT_NAMESPACE = 'default';

  private $id_handler;
  private $storage;
  private $listener;

  /** @var array Session values */
  private $values = array();

  /**
   * Specifies the number of seconds after which session will be automatically expired.
   * The setting must be set before {@link self::open()} is called. 
   * 
   * @var int
   */
  private $ttl = 1440;

  /** @var int First trace (timestamp), time when session was created */
  private $first_trace;

  /** @var int Last trace (Unix timestamp) */
  private $last_trace;

  /** @var int */
  private $requests_counter;

  /**
   * regenerate session id every X requests
   *
   * @var int Regenerate session id every X requests
   */
  private $regenerate_after_requests = 0;

  /**
   * regenerate session if after specified amount of seconds
   *
   * @var int Regenerate session id after specified time
   */
  private $regenerate_after_time = 1440;

  /** @var int */
  private $last_regeneration;

  /** @var bool */
  private $regenerated;
  
  private $fingerprint_generators = array();
  private $fingerprint = '';
  
  /** @var bool Is session opened? */
  private $opened = false;

  /**
   * Constructor
   *
   * @param cSession_IdProvider|string $id Session Id provider or name for default cookie provider
   * @param cSession_Cache $cache
   */
  public function __construct() {
    // registering pseudo-destructor, just in case someone forgets to close
    register_shutdown_function(array($this, 'close'));
  }

  /**
   * Creates new session
   *
   * It should be called only once at the beginning. If called for existing
   * session it ovewrites it (clears all values etc).
   * It can be replaced with {@link cSession::open()} (called with "true" argument)
   */
  public function create() {
    $this->getIdHandler()->generateId();

    $this->unsetAllValues();

    $this->first_trace = time();
    $this->updateLastTrace();

    $this->requests_counter = 1;
    $this->last_regeneration = time();

    $this->generateFingerprint();
    
    $this->opened = true;
  }

  /**
   * Opens the session (for a given request)
   *
   * If session hasn't been created earlier with {@link cSession::create()} method then :
   * - if argument is set to true, session will be created implicitly (behaves like PHP's native session_start()),
   * - otherwise, session won't be created and apprporiate listeners will be notified.
   *
   * If called earlier, then second (and next ones) call does nothing
   *
   * @param bool Create new session if not exists earlier?
   * @return bool
   */
  public function open($create_new_if_not_exists = false) {
    if (!$this->isOpened()) {
      $id_handler = $this->getIdHandler();

      if ($create_new_if_not_exists && !$id_handler->issetId()) {
        $this->create();
      } else {
        $this->generateFingerprint();
      }

      if ($id_handler->issetId()) {
        $this->load();

        $last_trace = $this->getLastTrace();

        if (is_null($last_trace)) { // no data found, either session adoption attempt (wrong sessid) or expired session data already removed
          $this->getListener()->trigger('sesshin.session.no_data_or_expired', array($this));
        } elseif ($last_trace + $this->getTtl() < time()) { // expired
          $this->getListener()->trigger('sesshin.session.expired', array($this));
        } elseif ($this->fingerprint != $this->getFingerprint()) {
          $this->getListener()->trigger('sesshin.session.invalid_fingerprint', array($this));
        } else {
          $this->opened = true;
          $this->requests_counter += 1;
        }
      }
    }

    return $this->opened;
  }

  /**
   * @return bool
   */
  public function isOpened() {
    return $this->opened;
  }

  public function isOpen() {
    return $this->isOpened();
  }

  /**
   * Close the session (for a given request).
   */
  public function close() {
    if ($this->opened) {
      $now = time();

      // id rotation (regenerate id)
      if (($this->regenerate_after_requests &&
        ($this->requests_counter % $this->regenerate_after_requests === 0)) ||
        (($this->last_regeneration && $this->regenerate_after_time) &&
        ($this->last_regeneration + $this->regenerate_after_time < $now))
      ) {
        $this->regenerateId();
        $this->last_regeneration = $now;
      }

      $this->updateLastTrace();
      $this->save();

      $this->unsetAllValues();
      $this->opened = false;
    }
  }

  /**
   * Destroy the session.
   */
  public function destroy() {
    $this->unsetAllValues();
    $this->getStorage()->delete($this->getId());
    $this->getIdHandler()->unsetId();
  }

  public function getId() {
    return $this->getIdHandler()->getId();
  }

  /**
   * Regenerates session id.
   *
   * Destroys current session in cache and generates new id, which will be saved
   * at the end of script execution (together with values).
   *
   * Id is regenerated at the most once per script execution (even if called a few times).
   *
   * Mitigates Session Fixation - use it whenever the user's privilege level changes.
   *
   * The method is also used by {@link cSession::setIdRotationRequests()} &
   * {@link cSession::setIdRotationTime()}.
   */
  public function regenerateId() {
    if (!$this->regenerated) {
      $this->getStorage()->destroy($this->getId());
      $this->getIdHandler()->generateId();
      $this->regenerated = true;

      return true;
    }

    return false;
  }

  public function setIdHandler(Id\Handler $id_handler) {
    $this->id_handler = $id_handler;
  }
  
  public function getIdHandler() {
    if (!$this->id_handler) {
      $this->id_handler = new Id\Handler();
    }
    return $this->id_handler;
  }

  public function setStorage(Storage\StorageInterface $storage) {
    $this->storage = $storage;
  }
  
  public function getStorage() {
    if (!$this->storage) {
      $this->storage = new Storage/Files();
    }
    return $this->storage;
  }

  public function setListener(Listener\Listener $listener) {
    if (!$this->listener) {
      $this->listener = new Listener\Listener();
    }
    $this->listener = $listener;
  }

  public function getListener() {
    return $this->listener;
  }

  public function addFingerprintGenerator(FingerprintGenerator\FingerprintGeneratorInterface $fingerprint_generator) {
    $this->fingerprint_generators[] = $fingerprint_generator;
  }
  
  protected function getFingerprintGenerators() {
    return $this->fingerprint_generators;
  }
  
  protected function generateFingerprint() {
    $this->fingerprint = '';
    foreach ($this->getFingerprintGenerators() as $fingerprint_generator) {
      $this->fingerprint .= $fingerprint_generator->generate();
    }
    return $this->fingerprint;
  }
  
  public function getFingerprint() {
    return $this->fingerprint;
  }

  /**
   * Gets first trace timestamp.
   * 
   * @return int
   */
  public function getFirstTrace() {
    return $this->first_trace;
  }

  /**
   * Updates last trace timestamp.
   */
  public function updateLastTrace() {
    $this->last_trace = time();
  }

  /**
   * Gets last trace timestamp.
   * 
   * @return int
   */
  public function getLastTrace() {
    return $this->last_trace;
  }

  public function setTtl($ttl) {
    if ($this->isOpened()) {
      throw new Exception('Session is already opened, ttl cannot be set');
    }  
    
    $this->ttl = $ttl;
    $this->getStorage()->setDefaultTtl($ttl);
  }
  
  public function getTtl() {
    return $this->ttl;
  }

  public function getRequestsCounter() {
    return $this->requests_counter;
  }

  public function getLastRegeneration() {
    return $this->last_regenertaion;
  }

  public function regenerateAfterRequests($requests) {
    $this->regenerate_after_requests = $requests;
  }

  public function regenerateAfterTime($time) {
    $this->regenerate_after_time = $time;
  }

  public function setValue($name, $value, $namespace = self::DEFAULT_NAMESPACE) {
    $this->values[$namespace][$name] = $value;
  }

  public function getValue($name, $namespace = self::DEFAULT_NAMESPACE) {
    return isset($this->values[$namespace][$name]) ? $this->values[$namespace][$name] : null;
  }

  public function getUnsetValue($name, $namespace = self::DEFAULT_NAMESPACE) {
    $value = $this->getValue($name, $namespace);
    $this->unsetValue($name, $namespace);
    return $value;
  }

  /**
   * Gets all (in general or namespaces's) session values
   *
   * @param option Namespace
   * @return array Session values
   */
  public function getValues($namespace = null) {
    return is_null($namespace) ? $this->values : $this->values[$namespace];
  }

  public function issetValue($name, $namespace = self::DEFAULT_NAMESPACE) {
    return isset($this->values[$namespace][$name]);
  }

  public function unsetValue($name, $namespace = self::DEFAULT_NAMESPACE) {
    unset($this->values[$namespace][$name]);
  }

  /**
   * Removes all session values.
   *
   * Note that it's metadata safe, i.e. it doesn't remove metadata
   */
  public function unsetAllValues($namespace = null) {
    if (!is_null($namespace)) {
      $this->values[$namespace] = array();
    } else {
      $this->values = array();
    }
  }

  public function offsetSet($offset, $value) {
    $this->setValue($offset, $value);
  }

  public function offsetGet($offset) {
    return $this->getValue($offset);
  }

  public function offsetExists($offset) {
    return $this->issetValue($offset);
  }

  public function offsetUnset($offset) {
    $this->unsetValue($offset);
  }

  public function __set($index, $value) {
    $this->setValue($index, $value);
  }

  public function __get($index) {
    return $this->getValue($index);
  }

  public function __isset($index) {
    return $this->issetValue($index);
  }

  public function __unset($index) {
    $this->unsetValue($index);
  }
  
  /**
   * Loads session data from cache.
   * @return bool
   */
  protected function load() {
    $values = $this->getStorage()->fetch($this->getId());

    if ($values === false) {
      return false;
    }

    // metadata
    $metadata = $values['_metadata'];
    $this->first_trace = $metadata['first_trace'];
    $this->last_trace = $metadata['last_trace'];
    $this->last_regeneration = $metadata['last_regeneration'];
    $this->requests_counter = $metadata['requests_count'];
    $this->fingerprint = $metadata['fingerprint'];

    // values
    $this->values = $values;

    return true;
  }

  /**
   * Saves session data into cache.
   * @return bool
   */
  protected function save() {
    $values = $this->values;

    $values['_metadata'] = array(
      'first_trace' => $this->getFirstTrace(),
      'last_trace' => $this->getLastTrace(),
      'last_regeneration' => $this->getLastRegeneration(),
      'requests_count' => $this->getRequestsCounter(),
      'fingerprint' => $this->fingerprint,
    );

    return $this->getStorage()->store($this->getId(), $values);
  }

}
