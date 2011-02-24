<?php
/**
 * Session handler
 *
 * @package Composit Session
 * @version SVN: $Id: $
 * @author Przemek Sobstel http://sobstel.org
 * @link http://segfaultlabs.com/composit/
 */

class cSession implements ArrayAccess, Countable, IteratorAggregate
{
	
	const DEFAULT_NAMESPACE = 'default';
	
    /**
     * @var string cSession_IdProvider
     */
	protected $idProvider;

    /**
     * @var array Session values
     */
    protected $values = array();
    
    /**
     * Specifies the number of seconds after which session will be automatically expired.
     * The setting must be set before {@link cSession::open()} is called. 
     * 
     * @var int Expiry time
     */
    protected $expiryTime = 1440; // 24 minutes
        
    /**
     * @var int First trace (timestamp), time when session was created
     */
    protected $firstTrace;
    
    /**
     * @var int Last trace (Unix timestamp)
     */
    protected $lastTrace;
        
    /**
     * @var int
     */
    protected $requestsCount;

    /**
     * regenerate session id every X requests
     *
     * @var int Regenerate session id every X requests
     */
    protected $regenerateAfterRequests = 0;

    /**
     * regenerate session if after specified amount of seconds
     *
     * @var int Regenerate session id after specified time
     */
    protected $regenerateAfterTime = 1440;

    /**
     * @var int
     */
    protected $lastRegeneration;
       
    /**
     * @var cSession_Cache
     */
    protected $cache;

    /**
     * @var bool
     */
    protected $regenerated;

    protected $fingerprint = '';

    /**
     * @var callback Fingerprint to check (additional protection), e.g. user agent
     */
    protected $fingerprintCallback;

    /**
     * @var array
     */
    protected $fingerprintCallbackParams = array();

    /**
     * @var bool Is session opened?
     */
    protected $opened = false;

    /**
     * Constructor
     *
     * @param cSession_IdProvider|string $id Session Id provider or name for default cookie provider
     * @param cSession_Cache $cache
     */
    public function __construct($idProvider = null, cCache $cache = null)
    {
		$this->idProvider = $idProvider;
        $this->cache = $cache;		

        // default expiry time
        $this->getCache()->setExpiryTime($this->expiryTime);
		
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
    public function create()
    {
        // generate new id
        $this->getIdProvider()->generateId();

        // clear all values
        $this->unsetAllValues();

        // set first trace
        $this->firstTrace = time();
        
        // update last trace
        $this->updateLastTrace();

        // needed for id rotation
        $this->requestsCount = 1;
        $this->lastRegeneration = time();
        
        // fingerprint
       	$this->fingerprint = $this->makeFingerprint();

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
    public function open($createNewIfNotExists = false)
    {    	
		if (!$this->isOpened())
		{
            $idProvider = $this->getIdProvider();

	        // if session not exists and not just created
            if (!$idProvider->issetId() && $createNewIfNotExists)
	        {
                $this->create();
	        }

	        // opens session
	        if ($idProvider->issetId())
	        {
	            $this->fetch();               

	            $lastTrace = $this->getLastTrace();
	                        
	            if (is_null($lastTrace)) // no data found, either session adoption attempt (wrong sessid) or expired session data already removed
	            {
                    $listenersCalledNum = cRegistry::getListener()->trigger('cSession.noDataOrExpired', array($this));
	            }
	            elseif ($lastTrace + $this->expiryTime < time()) // expired
	            {
                    $listenersCalledNum = cRegistry::getListener()->trigger('cSession.expired', array($this));
	            }
	            elseif ($this->fingerprint != $this->makeFingerprint()) // invalid fingerprint event
	            {
	            	$listenersCalledNum = cRegistry::getListener()->trigger('cSession.invalid', array($this));
	            }
	            else
	            {
	                $this->opened = true;
	                $this->requestsCount++;
	            }

                // if event occured and no listeners defined then restart session (for security sake)
                if (isset($listenersCalledNum) && ($listenersCalledNum == 0))
                {
                    $this->create();
                }
	        }
	    }  

		return $this->opened;
    }
    	
    /**
     * @return bool
     */
    public function isOpened()
    {
    	return $this->opened;
    }

	public function isOpen()
	{
		return $this->isOpened();
	}	
	
    /**
     * Closes the session (for a given request)
     *
     * Regenerates session Id, saves data, sets Id via Id provider, updates last trace, collects garbage.
     */
    public function close()
    {
        if ($this->opened)
        {
            $now = time();

            // id rotation (regenerate id)
            if (($this->regenerateAfterRequests &&
                ($this->requestsCount % $this->regenerateAfterRequests === 0)) ||
                (($this->lastRegeneration && $this->regenerateAfterTime) &&
                ($this->lastRegeneration + $this->regenerateAfterTime < $now))
               )
            {
                $this->regenerateId();
                $this->lastRegeneration = $now;
            }

            // save all data
            $this->updateLastTrace();
            $this->store();

            $this->opened = false;
        }
    }

    /**
     * Destroys the session (destroys data in cache, notifies Id provider)
     */
    public function destroy()
    {
        $this->unsetAllValues();

        $this->getCache()->delete($this->getId());

        $this->getIdProvider()->unsetId();
    }

    public function getId()
    {
        return $this->getIdProvider()->getId();
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
    public function regenerateId()
    {
    	if (!$this->regenerated)
    	{
            $this->getCache()->destroy($this->getId());
            $this->getIdProvider()->generateId();
        	$this->regenerated = true;
            
            return true;
    	}

        return false;
    }
    
    public function setFingerprintCallback($callback, array $params = array())
    {
        if (is_callable($callback))
       	{
           	$this->fingerprintCallback = $callback;
           	$this->fingerprintCallbackParams = $params;
       	}
       	else
       	{
       		throw new cSession_Exception('Provided callback is not valid');
       	}     	
    }
        
    /**
     * Gets first trace timestamp
     * 
     * @return int
     */
    public function getFirstTrace()
    {
    	return $this->firstTrace;
    }    
    
    /**
     * Updates last trace
     */
    public function updateLastTrace()
    {
        $this->lastTrace = time();
    }    
        
    /**
     * Gets last trace timestamp
     * 
     * @return int
     */
    public function getLastTrace()
    {
    	return $this->lastTrace;
    }
            
    public function setExpiryTime($time)
    {
    	if (!$this->id)
        {
        	$this->expiryTime = $time;
			$this->getCache()->setExpiryTime($time);
        }
    	else    	
    	{
    		throw new cSession_Exception('Expiry time not set, session is already opened');
    	}
    }    
    
    public function getRequestsCount()
    {
    	return $this->requestsCount;
    }

    public function getLastRegeneration()
    {
    	return $this->lastRegenertaion;
    }

    public function regenerateAfterRequests($requests)
    {
    	$this->regenerateAfterRequests = $requests;
    }
        
    public function regenerateAfterTime($time)
    {
    	$this->regenerateAfterTime = $time;
    }

    /**
     * @return cSession_IdProvider
     */
    public function getIdProvider()
    {
    	if (!($this->idProvider instanceof cSession_IdProvider))
    	{
    		$this->idProvider = new cSession_IdProvider_Cookie($this->idProvider);
    	}

    	return $this->idProvider;
    }


    /**
     * Get cache
     * 
     * @return cSession_Cache
     */
    public function getCache()
    {
    	if (is_null($this->cache))
    	{
    		$this->cache = new cCache_File();
    	}
    	
    	return $this->cache;
    }
            
    /**
     * @return string
     */
    protected function makeFingerprint()
    {
    	return (isset($this->fingerprintCallback) ?
    		call_user_func_array($this->fingerprintCallback, $this->fingerprintCallbackParams) :
    		'');
    }

    public function bindEvent($subevent, $callback, $id = null)
    {
        return cRegistry::getListener()->bind('cSession.'.$subevent, $callback, $id);
    }

    public function setValue($name, $value, $namespace = self::DEFAULT_NAMESPACE)
    {    	    	
        $this->values[$namespace][$name] = $value;
    }

    public function getValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        return isset($this->values[$namespace][$name]) ? $this->values[$namespace][$name] : null;
    }
    
    public function getUnsetValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
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
    public function getValues($namespace = null)
    {    	    
        return is_null($namespace) ? $this->values : $this->values[$namespace];
    }    

    public function issetValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        return isset($this->values[$namespace][$name]);
    }    
    
    public function unsetValue($name, $namespace = self::DEFAULT_NAMESPACE)
    {
        unset($this->values[$namespace][$name]);
    }    
    
    /**
     * Removes all session values.
     *
     * Note that it's metadata safe, i.e. it doesn't remove metadata
     */
    public function unsetAllValues($namespace = null)
    {
    	if (!is_null($namespace))
    	{
    		$this->values[$namespace] = array();
    	}
    	else
    	{
    		$this->values = array();
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
        
    public function count()
    {
    	return count($this->values);
    }
    
    public function getIterator()
    {
    	return new ArrayIterator($this->values);
    }
    
    public function __set($index, $value)
    {
    	$this->setValue($index, $value);
    }
    
    public function __get($index)
    {
    	return $this->getValue($index);
    }
    
    public function __isset($index)
    {
    	return $this->issetValue($index);
    }
    
    public function __unset($index)
    {
    	$this->unsetValue($index);
    }       
    
    /**
     * Loads session data from cache
     *
     * @return bool
     */
    protected function fetch()
    {
        $values = $this->getCache()->fetch($this->getId());

        if ($values === false)
        {
        	return false;
        }

        // metadata
        $metadata = $values['__cSession_metadata'];
        $this->firstTrace = $metadata['first_trace'];
        $this->lastTrace = $metadata['last_trace'];
        $this->lastRegeneration = $metadata['last_regeneration'];
        $this->requestsCount = $metadata['requests_count'];
        $this->fingerprint = $metadata['fingerprint'];        
        unset($values['__cSession_metadata']);  

        // values
        $this->values = $values;

        return true;
    }

    /**
     * Saves session data into cache
     *
     * @return bool
     */
    protected function store()
    {
        $values = $this->values;
        
        $values['__cSession_metadata'] =  array(
        	'first_trace' => $this->getFirstTrace(),
        	'last_trace' => $this->getLastTrace(),
            'last_regeneration' => $this->getLastRegeneration(),
        	'requests_count' => $this->getRequestsCount(),
        	'fingerprint' => $this->fingerprint
        );

        return $this->getCache()->store($this->getId(), $values);
    }

}
