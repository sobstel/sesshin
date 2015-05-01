<?php
namespace Sesshin\Store;

use Doctrine\Common\Cache\Cache;

/**
 * Uses doctrine/cache as a stroing engine.
 */
class DoctrineCache implements StoreInterface
{
    /** @var Cache */
    protected $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $id
     * @return bool|mixed
     */
    public function fetch($id)
    {
        return $this->cache->fetch($id);
    }

    /**
     * @param string $id
     * @param mixed $data
     * @param int $lifeTime
     * @return bool
     */
    public function save($id, $data, $lifeTime)
    {
        return $this->cache->save($id, $data, $lifeTime);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->cache->delete($id);
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }
}
