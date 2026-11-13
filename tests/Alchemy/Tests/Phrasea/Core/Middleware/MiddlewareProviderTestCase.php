<?php

namespace Alchemy\Tests\Phrasea\Core\Middleware;

abstract class MiddlewareProviderTestCase extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideDescription')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function differentInstancesShouldBereturnedEveryTime($service, $key)
    {
        self::$DI['app']->register(new $service());

        $instance1 = self::$DI['app'][$key];
        $instance2 = self::$DI['app'][$key];

        $this->assertTrue(is_callable($instance1));
        $this->assertSame($instance1, $instance2);
    }

    abstract public static function provideDescription();
}
