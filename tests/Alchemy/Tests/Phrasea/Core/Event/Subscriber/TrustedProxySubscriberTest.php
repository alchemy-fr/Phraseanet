<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\TrustedProxySubscriber;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

class TrustedProxySubscriberTest extends \PHPUnit_Framework_TestCase
{
    private function getConfigurationMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAllowedIpsAreSetAsArray()
    {
        $configuration = $this->getConfigurationMock();
        $configuration->expects($this->once())
            ->method('offsetGet')
            ->with('trusted-proxies')
            ->will($this->returnValue(array('8.8.8.8', '127.0.0.1')));

        $configuration->expects($this->once())
            ->method('offsetExists')
            ->with('trusted-proxies')
            ->will($this->returnValue(true));

        $app = new Application();
        $app['dispatcher']->addSubscriber(new TrustedProxySubscriber($configuration));
        $app->get('/', function () {
            return 'data';
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(array('8.8.8.8', '127.0.0.1'), Request::getTrustedProxies());
    }

    public function testAllowedIpsAreSetWhenEmpty()
    {
        $configuration = $this->getConfigurationMock();
        $configuration->expects($this->once())
            ->method('offsetExists')
            ->with('trusted-proxies')
            ->will($this->returnValue(false));

        $app = new Application();
        $app['dispatcher']->addSubscriber(new TrustedProxySubscriber($configuration));
        $app->get('/', function () {
            return 'data';
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(array(), Request::getTrustedProxies());
    }
}
