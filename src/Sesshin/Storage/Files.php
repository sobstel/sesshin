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

use DirectoryIterator;

class Files extends Storage {

	/**
	 * @var string
	 */
  private $path;
    
  /**
   * @var int Garbage collection probability (divisor is 100). 
   * Should be 0 <= x <= 100, where 0 means never and 100 means always.
   */	
	private $gc_probability = 1;

  /**
   * @param string optional Directory where cache is stored
   */
  public function __construct($path = '/tmp') {
    $this->path = realpath($path);
  }
  
  public function getPath() {
    return $this->path;
  }
  
  public function __destruct() {
    if ($this->getGcProbability() > 0) {
      if (mt_rand(1,100) <= $this->getGcProbability()) {
        $this->deleteExpired();
      }
    }
  }
  
  public function setGcProbability($gc_probability) {
    $this->gc_probability = $gc_probability;
  }
  
  public function getGcProbability() {
    return $this->gc_probability;
  }
  
  public function getTtl($ttl = null) {
    $ttl = parent::getTtl();
    if ($ttl === 0) {
      $ttl = PHP_INT_MAX;
    }
    return $ttl;
  }

	protected function doStore($key, $value, $ttl = null) {
		return (bool)file_put_contents($this->getPath().'/'.$key, $value, LOCK_EX);;
	}

	protected function doFetch($key) {
    $file = $this->getPath().'/'.$key;
    
    if (!file_exists($file)) {
      return false;
    }
    
    $threshold = time() - $this->getDefaultTtl();
    if (filemtime($file) < $threshold) {
      $this->doDelete($key);
      return false;
    }
    
    return unserialize(file_get_contents($file));
	}    
	
	protected function doDelete($key) {
    $file = $this->getPath().'/'.$key;
    if (file_exists($file)) {
      return unlink($file);
    }
    return false;
	}

  public function deleteExpired() {
    $threshold = time() - $this->getDefaultTtl();
    foreach(new DirectoryIterator($this->path) as $file) {
      if ($this->isSessionFile($file) && ($file->getMTime() < $threshold)) {
        unlink($file->getPathname());
      }
    }
  }

  public function isSessionFile(\SplFileInfo $file) {
    return ($file->isFile() && $file->isWritable() &&
        (strpos($file->getPathname(), $this->getPath()) === 0) &&
        (!$this->getPrefix() || (strpos($file->getFilename(), $this->getPrefix()) === 0)));
  }
	
}
