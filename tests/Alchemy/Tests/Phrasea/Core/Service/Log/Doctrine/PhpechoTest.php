<?php

namespace Alchemy\Tests\Phrasea\Core\Service\Log\Doctrine;

use Alchemy\Phrasea\Core\Service\Log\Doctrine\Phpecho;

class DoctrinePhpechoTest extends \PhraseanetPHPUnitAbstract
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
