<?php

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class OrmBuilderTest extends PhraseanetPHPUnitAbstract
{

    public function testCreateException()
    {
        $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
                array("type"    => "unknow", "options" => array())
        );

        try {
            $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, $configuration);
            $this->fail("An exception should be raised");
        } catch (\Exception $e) {

        }
    }

    public function testCreate()
    {
        $registry = $this->getMock("\RegistryInterface");

        $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
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

        $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }
}
