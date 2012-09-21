<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Builder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class LogBuilderTest extends PhraseanetPHPUnitAbstract
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
        $configuration = new ParameterBag(
                array("type"    => "Log\\Doctrine\\Monolog", "options" => array(
                        "handler"  => "rotate"
                        , "filename" => "test"
                        , 'channel'  => 'Test'
                        , 'output'   => 'json'
                        , 'max_day'  => '1'
                    )
                )
        );

        $service = Builder::create(self::$application, $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }

    public function testCreateNamespace()
    {
        $configuration = new ParameterBag(
                array("type"    => "Log\\Doctrine\\Phpecho", "options" => array())
        );

        $service = Builder::create(self::$application, $configuration);
        $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
    }
}
