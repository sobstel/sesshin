<?php

class cSession_IdProvider_Cookie extends cSession_IdProvider
{

    /**
     * @var string Actual id (value not populated in $_COOKIE yet)
     */
    protected $id;

	/**
     * @var string Session cookie name
     */
    protected $name;

    /**
     * @var string Session cookie path
     */
    protected $path;

    /**
    * @var string Session cookie domain
    */
    protected $domain;

    /**
     * SSL - secure session cookie
     *
     * @var bool Should cookie be secure?
     */
    protected $secure;

    /**
     * @var bool
     */
    protected $httpOnly;

    public function __construct($name, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        $this->name = (!empty($name) ? $name : 'sid');
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }

    /**
     * Sets session cookie (valid until browser is closed)
     */
    public function setId($id)
    {
		if (setcookie($this->name, $id, 0, $this->path, $this->domain, $this->secure, true))
        {
            $this->id = $id;
        }
    }

	public function getId()
	{
        if ($this->issetId())
        {
            return isset($this->id) ? $this->id : $_COOKIE[$this->name];
        }
	}

    public function issetId()
    {
        return (isset($this->id) || isset($_COOKIE[$this->name]));
    }

    /**
     * Removes session cookie
     */
    public function unsetId()
    {
		setcookie($this->name, '', 1, $this->path, $this->domain, $this->secure, true);        
    }

    /*
    /**
     * @param string
     *
    public function setCookiePath($path)
    {
    	$this->path = $path;
    }

    /**
     * @return string
     *
    public function getCookiePath()
    {
    	return $this->path;
    }

    /**
     * @param string
     *
    public function setCookieDomain($domain)
    {
    	$this->domain = $domain;
    }

    /**
     * @return string
     *
    public function getCookieDomain()
    {
    	return $this->domain;
    }

    /**
     * @param string
     *
    public function setCookieSecure($secure = true)
    {
    	$this->secure = $secure;
    }

    /**
     * @return string
     *
    public function isCookieSecure()
    {
    	return (bool)$this->secure;
    }
    */
    
}