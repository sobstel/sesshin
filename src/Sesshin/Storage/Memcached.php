<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Storage;

class Memcached extends Storage {

  private $memcached;

  public function __construct(\Memcached $memcached) {
    $this->memcached = $memcached;
  }

  public function getMemcached() {
    return $this->memcached;
  }

  protected function doStore($key, $value, $ttl = null) {
    return $this->getMemcached()->set($key, $value, $ttl);
  }

  protected function doFetch($key) {
    return $this->getMemcached()->get($key);
  }

  protected function doDelete($key) {
    return $this->getMemcached()->delete($key);
  }
}
