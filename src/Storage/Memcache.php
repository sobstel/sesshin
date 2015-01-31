<?php
namespace League\Sesshin\Storage;

class Memcache extends Storage
{
    private $memcache;

    public function __construct(\Memcache $memcache)
    {
        $this->memcache = $memcache;
    }

    public function getMemcache()
    {
        return $this->memcache;
    }

    protected function doStore($key, $value, $ttl = null)
    {
        return $this->getMemcache()->set($key, $value, null, $ttl);
    }

    protected function doFetch($key)
    {
        return $this->getMemcache()->get($key);
    }

    protected function doDelete($key)
    {
        // Memcache::delete() is unreliable
        return $this->getMemcache()->set($key, false, null, -1);
    }
}
