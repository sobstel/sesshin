<?php
namespace Sesshin\Tests\EntropyGenerator;

use Sesshin\EntropyGenerator\Uniq;

class UniqTest extends \PHPUnit\Framework\TestCase
{
    public function testGeneratesUniqueId()
    {
        $uniqGenerator = new Uniq();
        $this->assertRegExp('/\w+\.\w+/', $uniqGenerator->generate());
    }
}
