<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\FileServeServiceProvider;
use Silex\Application;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FileServeServiceProvider
 */
class FileServeServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\FileServeServiceProvider',
                'phraseanet.file-serve',
                'Alchemy\Phrasea\Http\ServeFileResponseFactory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\FileServeServiceProvider',
                'phraseanet.xsendfile-mapping',
                'Alchemy\Phrasea\Http\XsendfileMapping'
            ),
        );
    }

    public function testMapping()
    {
        $app = new Application();

        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());
        $app->register(new FileServeServiceProvider());
        $app['phraseanet.configuration.config-path'] = __DIR__ . '/fixtures/config-mapping.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/fixtures/config-mapping.php';
        $this->assertEquals(array(array(
            'directory' => '/tmp',
            'mount-point' => 'mount',
        ),array(
            'directory' => __DIR__ . '/../../../../../../tmp/download/',
            'mount-point' => '/download/',
        ),array(
            'directory' => __DIR__ . '/../../../../../../tmp/lazaret/',
            'mount-point' => '/lazaret/',
        )), $app['xsendfile.mapping']);

        unlink($app['phraseanet.configuration.config-compiled-path']);
    }
}
