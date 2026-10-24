<?php

namespace Alchemy\Tests\Geonames;

use Alchemy\Geonames\GeonamesServiceProvider;
use GuzzleHttp\ClientInterface;
use Pimple\Container;

class GeonamesServiceProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testRegister()
    {
        $app = new Container();
        $app->register(new GeonamesServiceProvider(), array(
            'geonames.server-uri' => 'http://geonames.domain.tld',
        ));

        $this->assertInstanceOf('Alchemy\Geonames\Connector', $app['geonames.connector']);
        $this->assertSame($app['geonames.connector'], $app['geonames.connector']);
        $this->assertInstanceOf(ClientInterface::class, $app['geonames.guzzle-client']);
    }

    public function testConnectorThrowsAnExceptionIfNoServerUri()
    {
        $app = new Container();
        $app->register(new GeonamesServiceProvider());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must set `geonames.server-uri`.');

        $app['geonames.connector'];
    }
}
