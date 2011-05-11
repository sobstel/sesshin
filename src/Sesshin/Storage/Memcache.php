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

class Memcache extends Storage {
  
  private $memcache;
  
  public function __construct(\Memcache $memcache) {
    $this->memcache = $memcache;
  }
  
  public function getMemcache() {
    return $this->memcache;
  }

	protected function doStore($key, $value, $ttl = null) {
		return $this->getMemcache()->set($key, $value, null, $ttl);
	}

	protected function doFetch($key) {
    return $this->getMemcache()->get($key);
	}

	protected function doDelete($id) {
    // Memcache::delete() is unreliable
    return $this->getMemcache()->set($key, false, null, -1);
	}

}
