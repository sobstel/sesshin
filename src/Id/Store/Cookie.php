<?php
namespace Sesshin\Id\Store;

use Sesshin\Exception;

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

    /**
     * @param string $name
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     */
    public function __construct($name = 'sid', $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        $this->name = $name;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        if (setcookie($this->name, $id, 0, $this->path, $this->domain, $this->secure, $this->httpOnly)) {
            $this->id = $id;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getId()
    {
        if ($this->issetId()) {
            return isset($this->id) ? $this->id : $_COOKIE[$this->name];
        }
        throw new Exception('Id is not set');
    }

    /**
     * @return bool
     */
    public function issetId()
    {
        return (isset($this->id) || isset($_COOKIE[$this->name]));
    }

    /**
     * @return void
     */
    public function unsetId()
    {
        setcookie($this->name, '', 1, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }
}
