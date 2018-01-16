<?php

    namespace Sesshin\Tests;

    use Sesshin\Session;
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


        function testSavingAndObtainingDataFromFlash()
        {
            $this->session->flash()->set('key1','Hello Hello1');
            $this->session->flash()->set('key2','Hello Hello2');

            $this->assertEquals('Hello Hello1',$this->session->flash()->get('key1'));
            $this->assertEquals('Hello Hello2',$this->session->flash()->get('key2'));
        }


        function testMovementFromNewDataToOldData(){
            $this->assertEmpty($this->session->flash()->get('key1'));
        }

    }
