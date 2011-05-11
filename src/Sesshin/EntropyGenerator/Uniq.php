<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Session\Entropy;

class Uniq implements EntropyGeneratorInterface {
  
  public function generate() {
    return uniqid(mt_rand(), true);
  }

}
