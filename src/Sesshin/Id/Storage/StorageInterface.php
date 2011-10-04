<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Id\Storage;

interface StorageInterface { 
  
  /**
   * @param int $id
   */
  public function setId($id);
  
  /**
   * @return string
   */
  public function getId();
  
  /**
   * @return bool
   */
  public function issetId();

  public function unsetId();

}
