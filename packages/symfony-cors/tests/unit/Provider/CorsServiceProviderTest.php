<?php

namespace Alchemy\CorsProvider\Tests;

use Alchemy\Cors\CorsListener;
use Alchemy\Cors\Options\DefaultProvider;
use Alchemy\Cors\Options\DefaultResolver;
use Alchemy\CorsProvider\CorsServiceProvider;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsServiceProviderTest extends TestCase
{
    private $cachePath;

    protected function tearDown(): void
    {
        if (null !== $this->cachePath) {
            @unlink($this->cachePath);
            @unlink($this->cachePath . '.meta');
        }
    }

    public function testServicesAreRegistered()
    {
        $app = $this->createApplication();
        $app->register(new CorsServiceProvider(), [
            'alchemy_cors.paths' => [
                '^/api/' => ['allow_origin' => '*', 'allow_methods' => ['GET']],
            ],
        ]);

        $config = $app['alchemy_cors.config'];
        $this->assertSame(['*'], $config['paths']['^/api/']['allow_origin']);
        $this->assertFalse($config['defaults']['allow_credentials']);

        $this->assertInstanceOf(DefaultProvider::class, $app['alchemy_cors.options_provider.config']);
        $this->assertNotSame($app['alchemy_cors.options_provider.config'], $app['alchemy_cors.options_provider.config']);
        $this->assertInstanceOf(DefaultResolver::class, $app['alchemy_cors.options_resolver']);
        $this->assertInstanceOf(CorsListener::class, $app['alchemy_cors.listener']);
        $this->assertSame($app['alchemy_cors.listener'], $app['alchemy_cors.listener']);

        $options = $app['alchemy_cors.options_resolver']->getOptionsFor(Request::create('/api/records'));
        $this->assertSame(['GET'], $options['allow_methods']);
    }

    public function testAdditionalProvidersAreSortedByPriority()
    {
        $app = $this->createApplication();
        $app->register(new CorsServiceProvider());

        $app['custom.provider'] = function () {
            return new DefaultProvider([], ['max_age' => 42]);
        };
        $app['alchemy_cors.options_providers'][] = ['service' => 'custom.provider', 'priority' => -10];

        $options = $app['alchemy_cors.options_resolver']->getOptionsFor(Request::create('/'));
        $this->assertSame(42, $options['max_age']);
    }

    public function testConfigIsCached()
    {
        $this->cachePath = sys_get_temp_dir() . '/' . uniqid('alchemy_cors', true) . '.php';

        $app = $this->createApplication();
        $app->register(new CorsServiceProvider(), [
            'alchemy_cors.cache_path' => $this->cachePath,
            'alchemy_cors.defaults' => ['max_age' => 12],
        ]);

        $config = $app['alchemy_cors.config'];

        $this->assertFileExists($this->cachePath);
        $this->assertSame($config, require $this->cachePath);
        $this->assertSame(12, $config['defaults']['max_age']);
    }

    public function testBootRegistersTheRequestListener()
    {
        $app = $this->createApplication();
        $app->register(new CorsServiceProvider());
        $app->boot();

        $listeners = $app['dispatcher']->getListeners(KernelEvents::REQUEST);

        $this->assertCount(1, $listeners);
        $this->assertSame([$app['alchemy_cors.listener'], 'onKernelRequest'], $listeners[0]);
        $this->assertSame(10000, $app['dispatcher']->getListenerPriority(KernelEvents::REQUEST, $listeners[0]));
    }

    private function createApplication()
    {
        $app = new Application();
        $app['dispatcher'] = function () {
            return new EventDispatcher();
        };

        return $app;
    }
}
