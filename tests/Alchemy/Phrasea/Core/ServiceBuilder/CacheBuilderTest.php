<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Builder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class CacheBuilderTest extends PhraseanetPHPUnitAbstract
{

    public function testCreateException()
    {
        $configuration = new ParameterBag(
                array("type" => "unknow")
        );

        try {
            $service = Builder::create(self::$application, $configuration);
            $this->fail("An exception should be raised");
        } catch (\Exception $e) {

        }
    }

    public function testCreate()
    {
        $configuration = new ParameterBag(
                array("type" => "Cache\\ArrayCache")
        );

        $service = Builder::create(self::$application, $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }
}
