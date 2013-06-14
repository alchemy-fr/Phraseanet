<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Client;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;

class BridgeExceptionSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorOnBridgeExceptions()
    {
        $app = new Application('test');
        $app['bridge.account'] = $this->getMockBuilder('Bridge_Account')
            ->disableOriginalConstructor()
            ->getMock();
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new BridgeExceptionSubscriber($app));
        $app->get('/', function () {
            throw new \Bridge_Exception('Bridge exception');
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testErrorOnOtherExceptions()
    {
        $app = new Application('test');
        $app['bridge.account'] = $this->getMockBuilder('Bridge_Account')
            ->disableOriginalConstructor()
            ->getMock();
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new BridgeExceptionSubscriber($app));
        $app->get('/', function () {
            throw new \InvalidArgumentException;
        });

        $client = new Client($app);
        $this->setExpectedException('\InvalidArgumentException');
        $client->request('GET', '/');
    }
}
