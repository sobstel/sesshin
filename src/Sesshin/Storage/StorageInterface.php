<?php

namespace Sesshin\Storage;

interface StorageInterface
{
    public function store($key, $value, $ttl = null);
    public function fetch($key);
    public function delete($key);
}
