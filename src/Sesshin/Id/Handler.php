<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Id;
use Sesshin\Id\Storage;
use Sesshin\Entropy;

class Handler {
  
  /** @var Storage\StorageInterface */
  private $id_storage;
  
  /** @var Entropy\EntropyGeneratorInterface */
  private $entropy_generator;
  
  /** @var string Hash algo used to generate session ID (it hashes entropy). */
  private $hash_algo = 'sha1';
  
  /**
   * @param Storage\StorageInterface $id_storage 
   */
  public function setIdStorage(Storage\StorageInterface $id_storage) {
    $this->id_storage = $id_storage;
  }
  
  /**
   * @return Storage\StorageInterface
   */
  public function getIdStorage() {
    if (!$this->id_storage) {
      $this->id_storage = new Storage\Cookie();
    }
    return $this->id_storage;
  }
  
  /**
   * Sets entropy that is used to generate session id.
   *
   * @param Entropy\EntropyGeneratorInterface $entropy_generator
   */
  public function setEntropyGenerator(Entropy\EntropyGeneratorInterface $entropy_generator) {
    $this->entropy_generator = $entropy_generator;
  }
  
  /**
   * @return Entropy\EntropyGeneratorInterface
   */
  public function getEntropyGenerator() {
    if (!$this->entropy_generator) {
      $this->entropy_generator = new Entropy\Uniq();
    }
    return $this->entropy_generator;
  }
  
  /**
   * @param string Hash algorith accepted by hash extension.
   */
  public function setHashAlgo($algo) {
    if (in_array($algo, hash_algos())) {
      $this->hash_algo = $algo;
    } else {
      throw new Exception('Provided algo is not valid (not on hash_algos() list)');
    }
  }
  
  /**
   * @return string 
   */
  public function getHashAlgo() {
    return $this->hash_algo;
  }
  
  /**
   * @return string 
   */
  public function generateId() {
    $id = hash($this->getHashAlgo(), $this->getEntropyGenerator()->generate());
    $this->setId($id);
    return $this->getId();
  }
  
  public function setId($id) {
    $this->getIdStorage()->setId($id);
  }
  
  public function getId() {
    return $this->getIdStorage()->getId();
  }
  
  public function issetId() {
    return $this->getIdStorage()->issetId();
  }
  
  public function unsetId() {
    return $this->getIdStorage()->unsetId();
  }

}
