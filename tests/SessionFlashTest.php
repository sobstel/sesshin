<?php

    namespace Sesshin\Tests;

    use Sesshin\Session;
    use Sesshin\SessionFlash;
    use Sesshin\Store\FileStore;

    class SessionFlashTest extends \PHPUnit\Framework\TestCase
    {
        /** @var Session */
        public $session;
        protected $temp_dir;

        function setUp()
        {
            $this->temp_dir = __DIR__.'/temp';
            $this->session = new Session(new FileStore($this->temp_dir));
        }


        function getSession()
        {
            return new Session(new FileStore($this->temp_dir));
        }


        function testAddFlashData()
        {
            $this->session->flash()->add('Hello Hello1');
            $this->session->flash()->add('Hello Hello2');
            $this->session->flash()->add('Hello Hello3');

            $this->assertTrue(true);
        }


        function testAddFlashDataOfASpecificType()
        {
            $this->session->flash()->add('Hello type 1','errors');
            $this->session->flash()->add('Hello type 2','success');

            $this->assertTrue(true);
        }

        function testGetTheFlashDataAdded()
        {
            $key1_value = $this->session->flash()->get();

            var_dump($key1_value);

            $this->assertEquals('Hello Hello1',$key1_value[0]);
            $this->assertEquals('Hello Hello2',$key1_value[1]);
            $this->assertEquals('Hello Hello3',$key1_value[2]);
        }


        function testTheFlashDataWasObtainedCanNotExist()
        {
            $key1_value = $this->session->flash()->get();

            $this->assertNull($key1_value,'the data was obtained and eliminate');
        }
    }
