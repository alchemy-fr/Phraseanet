<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Cache\ConnectionFactory;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Provider\SessionHandlerServiceProvider;
use Alchemy\Phrasea\Utilities\RedisSessionHandler;
use Alchemy\Tests\Phrasea\MockArrayConf;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\StrictSessionHandler;

class SessionHandlerServiceProviderTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousConfigurations')]
    public function testWithVariousConfigurations($sessionConf, $expectedInstance, $method = null, $options = null, $connectionClass = null)
    {
        if (null !== $connectionClass && !class_exists($connectionClass)) {
            $this->markTestSkipped(sprintf('%s extension is not available', $connectionClass));
        }

        $app = new Application();
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new SessionServiceProvider());
        $app->register(new SessionHandlerServiceProvider());

        $app['conf'] = new PropertyAccess(new MockArrayConf(['main' => ['session' => $sessionConf]]));
        $app['cache.connection-factory'] = $this->createMock(ConnectionFactory::class);
        if ($method) {
            $app['cache.connection-factory']->expects($this->once())
                ->method($method)
                ->with($options)
                ->willReturn($this->createMock($connectionClass));
        }

        $handler = $app['session.storage.handler.factory']->create($app['conf']);
        $this->assertInstanceOf($expectedInstance, $handler);
    }

    public static function provideVariousConfigurations()
    {
        @mkdir('/tmp/session');

        return [
            [
                ['type' => 'native'],
                StrictSessionHandler::class,
            ],
            [
                [
                    'type'    => 'file',
                    'options' => [
                        'save-path' => '/tmp/session',
                    ],
                ],
                NativeFileSessionHandler::class,
            ],
            [
                [
                    'type'    => 'memcached',
                    'options' => [
                        'host' => 'localhost',
                        'port' => '11211',
                    ],
                ],
                MemcachedSessionHandler::class,
                'getMemcachedConnection',
                ['host' => 'localhost', 'port' => 11211],
                'Memcached',
            ],
            [
                [
                    'type'    => 'redis',
                    'options' => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                    ],
                ],
                RedisSessionHandler::class,
                'getRedisConnection',
                ['host' => '127.0.0.1', 'port' => 6379],
                'Redis',
            ],
        ];
    }
}
