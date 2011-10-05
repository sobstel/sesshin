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

class Session implements \ArrayAccess {

  const DEFAULT_NAMESPACE = 'default';
  const METADATA_NAMESPACE = '__metadata__';

  /** @var Sesshin\Id\Handler */
  private $id_handler;

  /** @var int Number of requests after which id is regeneratd */
  private $id_requests_limit = NULL;

  /** @var int Time after id is regenerated */
  private $id_ttl = 1440;

  /** @var bool */
  private $id_regenerated;

  /** @var int */
  private $regeneration_trace;

  /** @var Sesshin\Storage\StorageIntrface */
  private $storage;

  /** @var Sesshin\Listener\Listener */
  private $listener;

  /** @var array Session values */
  private $values = array();

  /** @var int Specifies the number of seconds after which session will be automatically expired */
  private $ttl = 1440;

  /** @var int First trace (timestamp), time when session was created */
  private $first_trace;

  /** @var int Last trace (Unix timestamp) */
  private $last_trace;

  /** @var int */
  private $requests_counter;

  /** @var array of Sesshin\FingerprintGenerator\FingerprintGeneratorInterface */
  private $fingerprint_generators = array();

  /** @var string */
  private $fingerprint = '';

  /** @var bool Is session opened? */
  private $opened = false;

  /**
   * Constructor
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
   * It can be replaced with {@link self::open()} (called with "true" argument)
   * 
   * @return bool Session opened?
   */
  public function create() {
    $this->getIdHandler()->generateId();

    $this->values = array();

    $this->first_trace = time();
    $this->updateLastTrace();

    $this->requests_counter = 1;
    $this->regeneration_trace = time();

    $this->fingerprint = $this->generateFingerprint();

    $this->opened = true;

    return $this->opened;
  }

  /**
   * Opens the session (for a given request)
   *
   * If session hasn't been created earlier with {@link self::create()} method then:
   * - if argument is set to true, session will be created implicitly (behaves
   *   like PHP's native session_start()),
   * - otherwise, session won't be created and apprporiate listeners will be notified.
   *
   * If called earlier, then second (and next ones) call does nothing
   *
   * @param bool Create new session if not exists earlier?
   * @return bool Session opened?
   */
  public function open($create_new_if_not_exists = false) {
    if (!$this->isOpened()) {
      $id_handler = $this->getIdHandler();

      if ($create_new_if_not_exists && !$id_handler->issetId()) {
        return $this->create();
      }

      if ($id_handler->issetId()) {
        $this->load();

        $last_trace = $this->getLastTrace();
        $this->fingerprint = $this->generateFingerprint();

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

  /**
   * Alias of {@link self::isOpened()}.
   *
   * @return bool
   */
  public function isOpen() {
    return $this->isOpened();
  }

  /**
   * Close the session (for a given request).
   */
  public function close() {
    if ($this->opened) {
      if ($this->shouldRegenerateId()) {
        $this->regenerateId();
      }

      $this->updateLastTrace();
      $this->save();

      $this->values = array();
      $this->opened = false;
    }
  }

  /**
   * Destroy the session.
   */
  public function destroy() {
    $this->values = array();
    $this->getStorage()->delete($this->getId());
    $this->getIdHandler()->unsetId();
  }

  public function getId() {
    return $this->getIdHandler()->getId();
  }

  /**
   * Regenerates session id.
   *
   * Destroys current session in storage and generates new id, which will be saved
   * at the end of script execution (together with values).
   *
   * Id is regenerated at the most once per script execution (even if called a few times).
   *
   * Mitigates Session Fixation - use it whenever the user's privilege level changes.
   */
  public function regenerateId() {
    if (!$this->id_regenerated) {
      $this->getStorage()->destroy($this->getId());
      $this->getIdHandler()->generateId();

      $this->regeneration_trace = time();
      $this->id_regenerated = true;

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

  public function setIdRequestsLimit($limit) {
    $this->id_requests_limit = $limit;
  }

  public function setIdTtl($ttl) {
    $this->id_ttl = $ttl;
  }

  protected function shouldRegenerateId() {
    if ($this->id_requests_limit) {
      if ($this->requests_counter >= $this->id_requests_limit) {
        return true;
      }
    }

    if ($this->id_ttl && $this->regeneration_trace) {
      if ($this->regeneration_trace + $this->id_ttl < time()) {
        return true;
      }
    }

    return false;
  }

  public function setStorage(Storage\StorageInterface $storage) {
    $this->storage = $storage;
  }

  public function getStorage() {
    if (!$this->storage) {
      $this->storage = new Storage\Files();
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
    $fingerprint = '';
    foreach ($this->getFingerprintGenerators() as $fingerprint_generator) {
      $fingerprint .= $fingerprint_generator->generate();
    }
    return $fingerprint;
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
  protected function updateLastTrace() {
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

  /**
   * Gets last (id) regeneration timestamp.
   * 
   * @return int
   */
  public function getRegenerationTrace() {
    return $this->regeneration_trace;
  }

  /**
   *
   * It must be called before {@link self::open()}.
   *
   * @param type $ttl
   */
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

  public function getValues($namespace = self::DEFAULT_NAMESPACE) {
    return (isset($this->values[$namespace]) ? $this->values[$namespace] : array());
  }

  public function issetValue($name, $namespace = self::DEFAULT_NAMESPACE) {
    return isset($this->values[$namespace][$name]);
  }

  public function unsetValue($name, $namespace = self::DEFAULT_NAMESPACE) {
    if (isset($this->values[$namespace][$name])) {
      unset($this->values[$namespace][$name]);
    }
  }

  public function unsetValues($namespace = self::DEFAULT_NAMESPACE) {
    if (isset($this->values[$namespace])) {
      unset($this->values[$namespace]);
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
    $metadata = $values[self::METADATA_NAMESPACE];
    $this->first_trace = $metadata['first_trace'];
    $this->last_trace = $metadata['last_trace'];
    $this->regeneration_trace = $metadata['regeneration_trace'];
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

    $values[self::METADATA_NAMESPACE] = array(
      'first_trace' => $this->getFirstTrace(),
      'last_trace' => $this->getLastTrace(),
      'regeneration_trace' => $this->getRegenerationTrace(),
      'requests_count' => $this->getRequestsCounter(),
      'fingerprint' => $this->getFingerprint(),
    );

    return $this->getStorage()->store($this->getId(), $values);
  }

}
