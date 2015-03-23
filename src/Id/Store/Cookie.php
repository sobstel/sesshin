<?php
namespace Sesshin\Id\Store;

class Cookie implements StoreInterface
{
    /** @var string */
    private $id;

    /** @var string Session cookie name. */
    private $name;

    /** @var string Session cookie path */
    private $path;

    /** @var string Session cookie domain */
    private $domain;

    /** @var bool Should cookie be secure (SSL)? */
    private $secure;

    /** @var bool */
    private $httpOnly;

    public function __construct($name = 'sid', $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        $this->name = $name;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        if (setcookie($this->name, $id, 0, $this->path, $this->domain, $this->secure, $this->httpOnly)) {
            $this->id = $id;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        if ($this->issetId()) {
            return isset($this->id) ? $this->id : $_COOKIE[$this->name];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function issetId()
    {
        return (isset($this->id) || isset($_COOKIE[$this->name]));
    }

    /**
     * {@inheritdoc}
     */
    public function unsetId()
    {
        setcookie($this->name, '', 1, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }
}
