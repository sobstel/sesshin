<?php
/**
 * @package Composit Session
 * @version SVN: $Id: $
 * @author Przemek Sobstel http://sobstel.org
 * @link http://segfaultlabs.com/composit/
 */

abstract class cSession_IdProvider
{
 
    /**
     * @var callback Entropy callback used to generate session ID (result is hashed later)
     */
    protected $entropyCallback = array('cSession_Entropy', 'mt_uniq');

    /**
     * @var array
     */
    protected $entropyCallbackParams = array();

    /**
     * @var string Hash algo used to generate session ID (it hashes entropy)
     */
    protected $hashAlgo = 'sha1';

    /**
     * Generates unique id using hash algo and entropy callback.
     */
    public function generateId()
    {
        // generate entropy for hashing
        $entropy = call_user_func_array($this->entropyCallback, $this->entropyCallbackParams);

        // generate hash used as session id
        $id = hash($this->hashAlgo, $entropy);

        $this->setId($id);
    }

    abstract public function setId($id);
    abstract public function getId();
    abstract public function issetId();
    abstract public function unsetId();

    /**
     * Sets entropy callback that is used to generate session id.
     *
     * @param callback
     * @param array
     */
    public function setEntropyCallback($callback, array $params = array())
    {
        if (is_callable($callback))
       	{
           	$this->entropyCallback = $callback;
           	$this->entropyCallbackParams = $params;
       	}
       	else
       	{
       		throw new cSession_Exception('Provided callback is not valid');
       	}
    }

    /**
     * @param string Hash algorith accepted by hash extension
     */
    public function setHashAlgo($algo)
    {
    	if (in_array($algo, hash_algos()))
    	{
    		$this->hashAlgo = $algo;
    	}
       	else
       	{
       		throw new cSession_Exception('Provided algo is not valid (not on hash_algos() list)');
       	}
    }

}
