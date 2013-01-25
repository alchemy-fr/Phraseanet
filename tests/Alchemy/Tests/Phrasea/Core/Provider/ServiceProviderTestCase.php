<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

abstract class ServiceProviderTestCase extends \PhraseanetPHPUnitAbstract
{
    /**
     * @test
     * @dataProvider provideServiceDescription
     */
    public function theSameInstanceShouldBereturnedEveryTime($service, $key, $classname)
    {
        self::$DI['app']->register(new $service());

        $instance1 = self::$DI['app'][$key];
        $instance2 = self::$DI['app'][$key];

        $this->assertInstanceof($classname, $instance1);
        $this->assertEquals($instance1, $instance2);
    }

    abstract public function provideServiceDescription();
}
