<?php

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class CacheBuilderTest extends PhraseanetPHPUnitAbstract
{

    public function testCreateException()
    {
        $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
                array("type" => "unknow")
        );

        try {
            $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, $configuration);
            $this->fail("An exception should be raised");
        } catch (\Exception $e) {

        }
    }

    public function testCreate()
    {
        $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
                array("type" => "Cache\\ArrayCache")
        );

        $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }
}
