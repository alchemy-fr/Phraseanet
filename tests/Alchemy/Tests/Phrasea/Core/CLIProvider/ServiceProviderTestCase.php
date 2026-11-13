<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

abstract class ServiceProviderTestCase extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideServiceDescription')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function theSameInstanceShouldBereturnedEveryTime($service, $key, $classname)
    {
        $cli = self::$DI['cli'];
        $cli->register(new $service());

        $instance1 = $cli[$key];
        $instance2 = $cli[$key];

        $this->assertInstanceof($classname, $instance1);
        $this->assertEquals($instance1, $instance2);
    }

    abstract public static function provideServiceDescription();
}
