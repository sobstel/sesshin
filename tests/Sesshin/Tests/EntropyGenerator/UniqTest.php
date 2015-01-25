<?php
namespace Sesshin\Tests\EntropyGenerator;

use Sesshin\EntropyGenerator\Uniq;

class UniqTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratesUniqueId()
    {
        $uniq_generator = new Uniq();
        $this->assertRegExp('/\w+\.\w+/', $uniq_generator->generate());
    }
}
