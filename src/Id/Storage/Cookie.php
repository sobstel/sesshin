<?php
namespace League\Sesshin\Id\Storage;

class Cookie implements StorageInterface
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
    private $http_only;

    public function __construct($name = 'sid', $path = '/', $domain = null, $secure = false, $http_only = true)
    {
        $this->name = $name;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->http_only = $http_only;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        if (setcookie($this->name, $id, 0, $this->path, $this->domain, $this->secure, $this->http_only)) {
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
        setcookie($this->name, '', 1, $this->path, $this->domain, $this->secure, $this->http_only);
    }
}
