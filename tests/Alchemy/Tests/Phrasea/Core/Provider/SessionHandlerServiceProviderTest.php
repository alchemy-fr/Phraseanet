<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Provider\SessionHandlerServiceProvider;
use Alchemy\Tests\Phrasea\MockArrayConf;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionHandlerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SessionHandlerServiceProvider */
    private $sut;

    protected function setUp()
    {
        $this->sut = new SessionHandlerServiceProvider();
    }

    /**
     * @dataProvider provideVariousConfigurations
     */
    public function testWithVariousConfigurations($sessionConf, $expectedInstance, $method = null, $options = null, $mock = null)
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

    public function provideVariousConfigurations()
    {
        $configurations = [
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

        if (class_exists('Memcache')) {
            $memcache = $this->getMockBuilder('Memcache')
                ->disableOriginalConstructor()
                ->getMock();

            $configurations[] = [
                [
                    'type'    => 'memcache',
                    'options' => [
                        'host' => 'localhost',
                        'port' => '11211',
                    ]
                ],
                'Symfony\Component\HttpFoundation\Session\Storage\Handler\WriteCheckSessionHandler',
                'getMemcacheConnection',
                ['host' => 'localhost', 'port' => 11211],
                $memcache
            ];
        }

        if (class_exists('Memcached')) {
            // Error suppressor due to Memcached having now obsolete by reference declarations
            @$memcached = $this->getMockBuilder('Memcached')
                ->disableOriginalConstructor()
                ->getMock();

            $configurations[] = [
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
            ];
        }

        if (class_exists('Redis')) {
            $redis = $this->getMockBuilder('Redis')
                ->disableOriginalConstructor()
                ->getMock();

            $configurations[] = [
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
            ];
        }

        return $configurations;
    }

    public function testItIgnoresSubRequests()
    {
        $event = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::SUB_REQUEST)
        ;

        $this->sut->onKernelResponse($event);
    }

    public function testItSavesSessionAtKernelResponseEvent()
    {
        $session = $this->getMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;
        $session
            ->expects($this->once())
            ->method('save')
        ;

        $request = new Request();
        $request->setSession($session);

        $event = $this->getMockBuilder(FilterResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST)
        ;
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $this->sut->onKernelResponse($event);
    }

    public function testItAddsFilterResponseAtBoot()
    {
        $dispatcher = $this->getMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('addListener')
            ->with(KernelEvents::RESPONSE, [$this->sut, 'onKernelResponse'], -129);

        $app = new Application();
        $app['dispatcher'] = $dispatcher;

        $this->sut->boot($app);
    }
}
