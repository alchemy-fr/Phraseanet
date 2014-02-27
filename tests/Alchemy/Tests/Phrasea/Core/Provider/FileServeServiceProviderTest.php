<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\FileServeServiceProvider;

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
                'phraseanet.xsendfile-factory',
                'Alchemy\Phrasea\Http\XSendFile\XSendFileFactory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\FileServeServiceProvider',
                'phraseanet.h264-factory',
                'Alchemy\Phrasea\Http\H264PseudoStreaming\H264Factory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\FileServeServiceProvider',
                'phraseanet.h264',
                'Alchemy\Phrasea\Http\H264PseudoStreaming\H264Interface'
            ),
        );
    }

    public function testMapping()
    {
        $app = clone self::$DI['app'];
        $app['root.path'] = __DIR__ . '/../../../../../..';
        $app->register(new ConfigurationServiceProvider());
        $app->register(new FileServeServiceProvider());
        $app['phraseanet.configuration.config-path'] = __DIR__ . '/fixtures/config-mapping.yml';
        $app['phraseanet.configuration.config-compiled-path'] = __DIR__ . '/fixtures/config-mapping.php';
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NginxMode', $app['phraseanet.xsendfile-factory']->getMode());
        $this->assertEquals(1, count($app['phraseanet.xsendfile-factory']->getMode()->getMapping()));

        unlink($app['phraseanet.configuration.config-compiled-path']);
        unset($app);
    }
}
