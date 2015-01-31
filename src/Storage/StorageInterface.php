<?php
namespace League\Sesshin\Storage;

interface StorageInterface
{
    /**
     * Store session value
     */
    public function store($key, $value, $ttl = null);

    /**
     * Fetch session value
     */
    public function fetch($key);

    /**
     * Delete session value
     */
    public function delete($key);
}
