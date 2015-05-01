<?php
namespace Sesshin;

use League\Event\EmitterAwareTrait;
use Sesshin\Store\StoreInterface;

class Session implements \ArrayAccess
{
    use EmitterAwareTrait;

    const DEFAULT_NAMESPACE = 'default';
    const METADATA_NAMESPACE = '__metadata__';

    /*** @var \Sesshin\Id\Handler */
    private $idHandler;

    /*** @var int Number of requests after which id is regeneratd */
    private $idRequestsLimit = null;

    /*** @var int Time after id is regenerated */
    private $idTtl = 1440;

    /*** @var bool */
    private $idRegenerated;

    /*** @var int */
    private $regenerationTrace;

    /*** @var StoreInterface */
    private $store;

    /*** @var array Session values */
    private $values = array();

    /*** @var int Specifies the number of seconds after which session will be automatically expired */
    private $ttl = 1440;

    /*** @var int First trace (timestamp), time when session was created */
    private $firstTrace;

    /*** @var int Last trace (Unix timestamp) */
    private $lastTrace;

    /*** @var int */
    private $requestsCount;

    /*** @var array of \Sesshin\FingerprintGenerator\FingerprintGeneratorInterface */
    private $fingerprintGenerators = array();

    /*** @var string */
    private $fingerprint = '';

    /*** @var bool Is session opened? */
    private $opened = false;

    /**
     * @param StoreInterface $store
     */
    public function __construct(StoreInterface $store)
    {
        $this->store = $store;

        // registering shutdown function, just in case someone forgets to close session
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
    public function create()
    {
        $this->getIdHandler()->generateId();

        $this->values = array();

        $this->firstTrace = time();
        $this->updateLastTrace();

        $this->requestsCount = 1;
        $this->regenerationTrace = time();

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
    public function open($createNewIfNotExists = false)
    {
        if (!$this->isOpened()) {
            if ($this->getIdHandler()->issetId()) {
                $this->load();

                if (!$this->getFirstTrace()) {
                    $this->getEmitter()->emit(new Event\NoDataOrExpired($this));
                } elseif ($this->isExpired()) {
                    $this->getEmitter()->emit(new Event\Expired($this));
                } elseif ($this->generateFingerprint() != $this->getFingerprint()) {
                    $this->getEmitter()->emit(new Event\InvalidFingerprint($this));
                } else {
                    $this->opened = true;
                    $this->requestsCount += 1;
                }
            } elseif ($createNewIfNotExists) {
                $this->create();
            }
        }

        return $this->opened;
    }

    /**
     * Is session opened?
     *
     * @return bool
     */
    public function isOpened()
    {
        return $this->opened;
    }

    /**
     * Alias of {@link self::isOpened()}.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->isOpened();
    }

    /**
     * Is session expired?
     *
     * @return bool
     */
    public function isExpired()
    {
        return ($this->getLastTrace() + $this->getTtl() < time());
    }

    /**
     * Close the session.
     */
    public function close()
    {
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
    public function destroy()
    {
        $this->values = array();
        $this->getStore()->delete($this->getId());
        $this->getIdHandler()->unsetId();
    }

    /**
     * Get session identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->getIdHandler()->getId();
    }

    /**
     * Regenerates session id.
     *
     * Destroys current session in store and generates new id, which will be saved
     * at the end of script execution (together with values).
     *
     * Id is regenerated at the most once per script execution (even if called a few times).
     *
     * Mitigates Session Fixation - use it whenever the user's privilege level changes.
     */
    public function regenerateId()
    {
        if (!$this->idRegenerated) {
            $this->getStore()->delete($this->getId());
            $this->getIdHandler()->generateId();

            $this->regenerationTrace = time();
            $this->idRegenerated = true;

            return true;
        }

        return false;
    }

    public function setIdHandler(Id\Handler $idHandler)
    {
        $this->idHandler = $idHandler;
    }

    public function getIdHandler()
    {
        if (!$this->idHandler) {
            $this->idHandler = new Id\Handler();
        }

        return $this->idHandler;
    }

    public function setIdRequestsLimit($limit)
    {
        $this->idRequestsLimit = $limit;
    }

    public function setIdTtl($ttl)
    {
        $this->idTtl = $ttl;
    }

    /**
     * Determine if session id should be regenerated? (based on request_counter or regenerationTrace)
     */
    protected function shouldRegenerateId()
    {
        if (($this->idRequestsLimit) && ($this->requestsCount >= $this->idRequestsLimit)) {
            return true;
        }

        if (($this->idTtl && $this->regenerationTrace) && ($this->regenerationTrace + $this->idTtl < time())) {
            return true;
        }

        return false;
    }

    /**
     * @return StoreInterface
     */
    protected function getStore()
    {
        return $this->store;
    }

    public function addFingerprintGenerator(FingerprintGenerator\FingerprintGeneratorInterface $fingerprintGenerator)
    {
        $this->fingerprintGenerators[] = $fingerprintGenerator;
    }

    /**
     * @return string
     */
    protected function generateFingerprint()
    {
        $fingerprint = '';

        foreach ($this->fingerprintGenerators as $fingerprintGenerator) {
            $fingerprint .= $fingerprintGenerator->generate();
        }

        return $fingerprint;
    }

    /**
     * @return string
     */
    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    /**
     * Gets first trace timestamp.
     *
     * @return int
     */
    public function getFirstTrace()
    {
        return $this->firstTrace;
    }

    /**
     * Updates last trace timestamp.
     */
    protected function updateLastTrace()
    {
        $this->lastTrace = time();
    }

    /**
     * Gets last trace timestamp.
     *
     * @return int
     */
    public function getLastTrace()
    {
        return $this->lastTrace;
    }

    /**
     * Gets last (id) regeneration timestamp.
     *
     * @return int
     */
    public function getRegenerationTrace()
    {
        return $this->regenerationTrace;
    }

    /**
     * It must be called before {@link self::open()}.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        if ($this->isOpened()) {
            throw new Exception('Session is already opened, ttl cannot be set');
        }

        if ($ttl < 1) {
            throw new Exception('$ttl must be greather than 0');
        }

        $this->ttl = (int)$ttl;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @return int
     */
    public function getRequestsCount()
    {
        return $this->requestsCount;
    }

    /**
     * Sets session value in given or default namespace
     *
     * @param string $name
     * @param mixed $value
     * @param string $namespace
     */
    public function setValue($name, $value, $namespace = self::DEFAULT_NAMESPACE)
    {
        $this->values[$namespace][$name] = $value;
    }

    /**
     * Gets session value from given or default namespace
     *
     * @param string $name
     * @param string $namespace
     * @return mixed
     */
    public function getValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        return isset($this->values[$namespace][$name]) ? $this->values[$namespace][$name] : null;
    }

    /**
     * Gets and unsets value (flash value) for given or default namespace
     *
     * @param string $name
     * @param string $namespace
     * @return mixed
     */
    public function getUnsetValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        $value = $this->getValue($name, $namespace);
        $this->unsetValue($name, $namespace);

        return $value;
    }

    /**
     * Get all values for given or default namespace
     *
     * @param string $namespace
     * @return array
     */
    public function getValues($namespace = self::DEFAULT_NAMESPACE)
    {
        return (isset($this->values[$namespace]) ? $this->values[$namespace] : array());
    }

    public function issetValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        return isset($this->values[$namespace][$name]);
    }

    public function unsetValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        if (isset($this->values[$namespace][$name])) {
            unset($this->values[$namespace][$name]);
        }
    }

    public function unsetValues($namespace = self::DEFAULT_NAMESPACE)
    {
        if (isset($this->values[$namespace])) {
            unset($this->values[$namespace]);
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->setValue($offset, $value);
    }

    public function offsetGet($offset)
    {
        return $this->getValue($offset);
    }

    public function offsetExists($offset)
    {
        return $this->issetValue($offset);
    }

    public function offsetUnset($offset)
    {
        $this->unsetValue($offset);
    }

    /**
     * Loads session data from defined store.
     *
     * @return bool
     */
    protected function load()
    {
        $values = $this->getStore()->fetch($this->getId());

        if ($values === false) {
            return false;
        }

        // metadata
        $metadata = $values[self::METADATA_NAMESPACE];
        $this->firstTrace = $metadata['firstTrace'];
        $this->lastTrace = $metadata['lastTrace'];
        $this->regenerationTrace = $metadata['regenerationTrace'];
        $this->requestsCount = $metadata['requestsCount'];
        $this->fingerprint = $metadata['fingerprint'];

        // values
        $this->values = $values;

        return true;
    }

    /**
     * Saves session data into defined store.
     *
     * @return bool
     */
    protected function save()
    {
        $values = $this->values;

        $values[self::METADATA_NAMESPACE] = [
          'firstTrace' => $this->getFirstTrace(),
          'lastTrace' => $this->getLastTrace(),
          'regenerationTrace' => $this->getRegenerationTrace(),
          'requestsCount' => $this->getRequestsCount(),
          'fingerprint' => $this->getFingerprint(),
        ];

        return $this->getStore()->save($this->getId(), $values, $this->ttl);
    }
}
