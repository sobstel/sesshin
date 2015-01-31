<?php
namespace League\Sesshin\Tests\EntropyGenerator;

use League\Sesshin\EntropyGenerator\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    public function testUsesUrandomFileByDefault()
    {
        $this->assertAttributeEquals('/dev/urandom', 'file', new File());
    }

    public function testReads512BytesFromFileByDefault()
    {
        $urandom_generator = new File(__DIR__.'/files/urandom.txt');
        $entropy = $urandom_generator->generate();

        $this->assertEquals(strlen($entropy), 512);
    }

    public function testBytesReadFromFileCanBeSpecified()
    {
        $urandom_generator = new File(__DIR__.'/files/urandom.txt', 64);
        $entropy = $urandom_generator->generate();

        $this->assertEquals(strlen($entropy), 64);
    }

    /**
     * @expectedException \League\Sesshin\Exception
     */
    public function testThrowsExceptionOnEmptyFile()
    {
        $file_generator = new File(__DIR__.'/files/empty.txt');
        $file_generator->generate();
    }
}