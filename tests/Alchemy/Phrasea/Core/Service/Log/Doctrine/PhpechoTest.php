<?php

require_once __DIR__ . '/../../../../../../PhraseanetPHPUnitAbstract.class.inc';

class DoctrinePhpechoTest extends PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Phpecho(
                self::$DI['app'], array()
        );

        $this->assertInstanceOf("\Doctrine\DBAL\Logging\EchoSQLLogger", $log->getDriver());
    }

    public function testType()
    {
        $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Phpecho(
                self::$DI['app'], array()
        );

        $this->assertEquals("phpecho", $log->getType());
    }
}
