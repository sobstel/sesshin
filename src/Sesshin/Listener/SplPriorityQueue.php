<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Listener;

class SplPriorityQueue extends \SplPriorityQueue {

  private $serial = PHP_INT_MAX;

  public function insert($value, $priority) {
    $this->serial -= 1;
    parent::insert($value, array($priority, $this->serial));
  }
  
}
