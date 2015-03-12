<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Event\Subscriber\TrustedProxySubscriber;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider
 */
class ConfigurationServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'configuration.store',
                'Alchemy\\Phrasea\\Core\\Configuration\\HostConfiguration'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'conf',
                'Alchemy\\Phrasea\\Core\\Configuration\\PropertyAccess'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'phraseanet.configuration',
                'Alchemy\\Phrasea\\Core\\Configuration\\HostConfiguration'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'registry.manipulator',
                'Alchemy\\Phrasea\\Core\\Configuration\\RegistryManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'conf.restrictions',
                'Alchemy\Phrasea\Core\Configuration\AccessRestriction'
            ],
        ];
    }

    public function testItAddsRequestTrustedProxiesSubscriberOnBoot()
    {
        $app = new Application();
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.configuration.config-path'] = __DIR__ . '/fixtures/config-proxies.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/fixtures/config-proxies.php';
        $app->boot();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $listener = null;
        $method = null;
        foreach ($dispatcher->getListeners(KernelEvents::REQUEST) as $callable) {
            // Only look for TrustedProxySubscriber instances
            if (!is_array($callable)) {
                continue;
            }
            list($listener, $method) = $callable;
            if ($listener instanceof TrustedProxySubscriber) {
                break;
            }
        }
        $this->assertInstanceOf(
            'Alchemy\Phrasea\Core\Event\Subscriber\TrustedProxySubscriber',
            $listener,
            'TrustedProxySubscriber was not properly registered'
        );
        $this->assertEquals('setProxyConf', $method);

        $this->assertSame([], Request::getTrustedProxies());
        $listener->setProxyConf();
        $this->assertSame(['127.0.0.1', '66.6.66.6'], Request::getTrustedProxies());

        unlink($app['phraseanet.configuration.config-compiled-path']);
    }
}
