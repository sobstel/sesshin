<?php
namespace League\Sesshin\Store;

use Doctrine\Common\Cache\Cache;

/**
 * Uses doctrine/cache as a stroing engine.
 */
class DoctrineCache implements StoreInterface
{
    /*** @var Cache */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return $this->cache->fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return $this->cache->save($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
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
