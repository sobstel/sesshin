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

abstract class Storage implements StorageInterface {

  private $prefix;
  
  private $default_ttl = 1440;
  
  public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}
  
  public function getPrefix() {
    return $this->prefix;
  }
  
  public function setDefaultTtl($ttl) {
    $this->default_ttl = $ttl;
  }
  
  public function getDefaultTtl() {
    return $this->default_ttl;
  }
  
  public function getTtl($ttl = null) {
    return ($ttl !== null) ? $ttl : $this->getDefaultTtl();
  }

  protected function prepareKey($key) {
    $prefix = $this->getPrefix();
    if ($prefix) {
      $prefix .= '.';
    }
    return $prefix.$id;
  }
  
  public function store($key, $value, $ttl = null) {
    return $this->doStore($this->prepareKey($key), serialize($value), $ttl);
  }
  
  abstract protected function doStore($key, $value, $ttl = null);
  
  public function fetch($key) {
    $value = @unseralize($this->doFetch($this->prepareKey($key)));
    if ($value === false) {
      return false;
    }
    return $value;
  }
  
  abstract protected function doFetch($key);
  
  public function delete($key) {
    return $this->doDelete($this->prepareKey($key));
  }
  
  abstract protected function doDelete($key);

}
