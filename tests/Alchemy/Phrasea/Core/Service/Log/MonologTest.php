<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class MonologTest extends PhraseanetPHPUnitAbstract
{
    protected $options;

    public function setUp()
    {
        parent::setUp();
        $this->options = array(
            "handler"  => "rotate"
            , "filename" => "test"
            , "channel"  => "test"
        );
    }

    public function testService()
    {
        $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                self::$application, $this->options
        );

        $this->assertInstanceOf("\Monolog\Logger", $log->getDriver());
    }

    public function testType()
    {
        $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                self::$application, $this->options
        );

        $this->assertEquals("monolog", $log->getType());
    }

    public function testExceptionMissingOptions()
    {
        try {
            $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    self::$application, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testExceptionMissingHandler()
    {
        try {
            unset($this->options["handler"]);
            $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    self::$application, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testExceptionUnknowHandler()
    {
        try {
            $this->options["handler"] = "unknowHandler";
            $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    self::$application, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testMissingFile()
    {
        try {
            unset($this->options["filename"]);
            $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    self::$application, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testStreamLogger()
    {

        $this->options["handler"] = "stream";
        $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                self::$application, $this->options
        );
        $this->assertInstanceOf("\Monolog\Logger", $log->getDriver());
    }
}
