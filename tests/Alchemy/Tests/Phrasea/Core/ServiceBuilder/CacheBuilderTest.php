<?php

namespace Alchemy\Tests\Phrasea\Core\ServiceBuilder;

use Alchemy\Phrasea\Core\Service\Builder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class CacheBuilderTest extends \PhraseanetPHPUnitAbstract
{

    public function testCreateException()
    {
        $configuration = new ParameterBag(
                array("type" => "unknow")
        );

        try {
            $service = Builder::create(self::$DI['app'], $configuration);
            $this->fail("An exception should be raised");
        } catch (\Exception $e) {

        }
    }

    public function testCreate()
    {
        $configuration = new ParameterBag(
                array("type" => "Cache\\ArrayCache")
        );

        $service = Builder::create(self::$DI['app'], $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }
}
