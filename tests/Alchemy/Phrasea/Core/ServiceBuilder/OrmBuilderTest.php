<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Builder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class OrmBuilderTest extends PhraseanetPHPUnitAbstract
{

    public function testCreateException()
    {
        $configuration = new ParameterBag(
                array("type"    => "unknow", "options" => array())
        );

        try {
            $service = Builder::create(self::$application, $configuration);
            $this->fail("An exception should be raised");
        } catch (\Exception $e) {

        }
    }

    public function testCreate()
    {
        $registry = $this->getMock("\RegistryInterface");

        $configuration = new ParameterBag(
                array("type"    => "Orm\\Doctrine", "options" => array(
                        "debug" => false
                        , "log"   => array('service' => "Log\\query_logger")
                        , "dbal"    => "main_connexion"
                        , "cache"   => array(
                            "metadata" => "Cache\\array_cache"
                            , "query"    => "Cache\\array_cache"
                            , "result"   => "Cache\\array_cache"
                        )
                    )
                )
        );

        $service = Builder::create(self::$application, $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }
}
