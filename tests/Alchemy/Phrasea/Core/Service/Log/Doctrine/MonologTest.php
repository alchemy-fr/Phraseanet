<?php

require_once __DIR__ . '/../../../../../../PhraseanetPHPUnitAbstract.class.inc';

class DoctrineMonologTest extends PhraseanetPHPUnitAbstract
{
    protected $options = array(
        "handler"  => "rotate"
        , "filename" => "test"
        , 'output'   => 'json'
        , 'channel'  => 'test'
    );

    public function testService()
    {

        $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
                self::$application, $this->options
        );

        $this->assertInstanceOf("\Doctrine\Logger\MonologSQLLogger", $log->getDriver());
    }

    public function testType()
    {
        $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
                self::$application, $this->options
        );

        $this->assertEquals("doctrine_monolog", $log->getType());
    }

    public function testExceptionBadOutput()
    {
        try {
            $this->options["output"] = "unknowOutput";
            $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
                    self::$application, $this->options
            );
            $log->getDriver();
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }
}
