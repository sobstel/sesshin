<?php
namespace League\Sesshin\Storage;

class Memcached extends Storage
{
    private $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    public function getMemcached()
    {
        return $this->memcached;
    }

    protected function doStore($key, $value, $ttl = null)
    {
        return $this->getMemcached()->set($key, $value, $ttl);
    }

    protected function doFetch($key)
    {
        return $this->getMemcached()->get($key);
    }

    protected function doDelete($key)
    {
        return $this->getMemcached()->delete($key);
    }
}
