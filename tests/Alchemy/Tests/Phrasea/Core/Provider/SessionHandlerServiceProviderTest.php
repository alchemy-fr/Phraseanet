<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Provider\SessionHandlerServiceProvider;
use Alchemy\Tests\Tools\TranslatorMockTrait;
use Alchemy\Tests\Phrasea\MockArrayConf;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;

class SessionHandlerServiceProviderTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideVariousConfs
     */
    public function testWithVariousConf($sessionConf, $expectedInstance, $method = null, $options = null, $mock = null)
    {
        $app = new Application();
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new SessionServiceProvider());
        $app->register(new SessionHandlerServiceProvider());

        $app['conf'] = new PropertyAccess(new MockArrayConf(['main' => ['session' => $sessionConf ]]));
        $app['cache.connection-factory'] = $this->getMockBuilder('Alchemy\Phrasea\Cache\ConnectionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        if ($method) {
            $app['cache.connection-factory']->expects($this->once())
                ->method($method)
                ->with($options)
                ->will($this->returnValue($mock));
        }

        $handler = $app['session.storage.handler.factory']->create($app['conf']);
        $this->assertInstanceOf($expectedInstance, $handler);
    }

    public function provideVariousConfs()
    {
        $memcache = $this->getMockBuilder('Memcache')
             ->disableOriginalConstructor()
             ->getMock();

        $memcached = $this->getMockBuilder('Memcached')
            ->disableOriginalConstructor()
            ->getMock();

        $redis = $this->getMockBuilder('Redis')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            [
                [
                    'type' => 'memcache',
                    'options' => [
                        'host' => 'localhost',
                        'port' => '11211',
                    ]
                ],
                'Symfony\Component\HttpFoundation\Session\Storage\Handler\WriteCheckSessionHandler',
                'getMemcacheConnection',
                ['host' => 'localhost', 'port' => 11211],
                $memcache
            ],
            [
                [
                    'type' => 'memcached',
                    'options' => [
                        'host' => 'localhost',
                        'port' => '11211',
                    ]
                ],
                'Symfony\Component\HttpFoundation\Session\Storage\Handler\WriteCheckSessionHandler',
                'getMemcachedConnection',
                ['host' => 'localhost', 'port' => 11211],
                $memcached
            ],
            [
                [
                    'type' => 'redis',
                    'options' => [
                        'host' => '127.0.0.1',
                        'port' => '6379',
                    ]
                ],
                'Symfony\Component\HttpFoundation\Session\Storage\Handler\WriteCheckSessionHandler',
                'getRedisConnection',
                ['host' => '127.0.0.1', 'port' => 6379],
                $redis
            ],
            [
                [
                    'main' => [
                        'session' => [
                            'type' => 'file',
                        ]
                    ]
                ],
                'Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler'
            ]
        ];
    }
}
