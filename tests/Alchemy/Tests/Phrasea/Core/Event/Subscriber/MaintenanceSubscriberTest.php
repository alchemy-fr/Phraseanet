<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Subscriber\MaintenanceSubscriber;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MaintenanceSubscriberTest extends \PhraseanetTestCase
{
    public function tearDown()
    {
        if (is_file(__DIR__ . '/Fixtures/configuration-maintenance.php')) {
            unlink(__DIR__ . '/Fixtures/configuration-maintenance.php');
        }
        parent::tearDown();
    }

    public function testCheckNegative()
    {
        $app = new Application(Application::ENV_TEST);
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new MaintenanceSubscriber($app));
        $app->get('/', function () {
            return 'Hello';
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('Hello', $client->getResponse()->getContent());
    }

    public function testCheckPositive()
    {
        $app = new Application(Application::ENV_TEST);

        $app['phraseanet.configuration.config-path'] = __DIR__ . '/Fixtures/configuration-maintenance.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/Fixtures/configuration-maintenance.php';

        if (is_file($app['phraseanet.configuration.config-compiled-path'])) {
            unlink($app['phraseanet.configuration.config-compiled-path']);
        }

        $app['configuration.store'] = new HostConfiguration(new Configuration(
            $app['phraseanet.configuration.yaml-parser'],
            $app['phraseanet.configuration.compiler'],
            $app['phraseanet.configuration.config-path'],
            $app['phraseanet.configuration.config-compiled-path'],
            $app['debug']
        ));

        $app['conf'] = new PropertyAccess($app['configuration.store']);

        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new MaintenanceSubscriber($app));
        $app->get('/', function () {
            return 'Hello';
        });

        $client = new Client($app);
        try {
            $client->request('GET', '/');
            $this->fail('An exception should have been raised');
        } catch (HttpException $e) {
            $this->assertEquals(503, $e->getStatusCode());
            $this->assertEquals(['Retry-After' => 3600], $e->getHeaders());
        }
    }
}
