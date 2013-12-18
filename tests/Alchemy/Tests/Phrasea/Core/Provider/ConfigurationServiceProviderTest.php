<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider
 */
class ConfigurationServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'configuration.store',
                'Alchemy\\Phrasea\\Core\\Configuration\\Configuration'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'conf',
                'Alchemy\\Phrasea\\Core\\Configuration\\PropertyAccess'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider',
                'phraseanet.configuration',
                'Alchemy\\Phrasea\\Core\\Configuration\\Configuration'
            ],
        ];
    }

    public function testRequestTrustedProxiesAreSetOnRequest()
    {
        $app = $this->loadApp();
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.configuration.config-path'] = __DIR__ . '/fixtures/config-proxies.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/fixtures/config-proxies.php';
        $this->assertSame([], Request::getTrustedProxies());
        $app->boot();

        $app->get('/', function () {
            return 'data';
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertSame(['127.0.0.1', '66.6.66.6'], Request::getTrustedProxies());

        unlink($app['phraseanet.configuration.config-compiled-path']);
    }
}
