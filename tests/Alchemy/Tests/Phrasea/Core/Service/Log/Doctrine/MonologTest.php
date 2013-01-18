<?php

namespace Alchemy\Tests\Phrasea\Core\Service\Log\Doctrine;

use Alchemy\Phrasea\Core\Service\Log\Monolog;

class DoctrineMonologTest extends \PhraseanetPHPUnitAbstract
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
                self::$DI['app'], $this->options
        );

        $this->assertInstanceOf("\Doctrine\Logger\MonologSQLLogger", $log->getDriver());
    }

    public function testType()
    {
        $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
                self::$DI['app'], $this->options
        );

        $this->assertEquals("doctrine_monolog", $log->getType());
    }

    public function testExceptionBadOutput()
    {
        try {
            $this->options["output"] = "unknowOutput";
            $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
                    self::$DI['app'], $this->options
            );
            $log->getDriver();
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }
}
