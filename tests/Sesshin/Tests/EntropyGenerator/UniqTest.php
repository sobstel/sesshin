<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Tests\EntropyGenerator;
use Sesshin\EntropyGenerator\Uniq;

class UniqTest extends \PHPUnit_Framework_TestCase {

  public function testGeneratesUniqueId() {
    $uniq_generator = new Uniq();
    $this->assertRegExp('/\w+\.\w+/', $uniq_generator->generate());
  }

}
