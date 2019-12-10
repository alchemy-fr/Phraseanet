<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Client;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;

/**
 * @group functional
 * @group legacy
 */
class BridgeExceptionSubscriberTest extends \PhraseanetTestCase
{
    public function testErrorOnBridgeExceptions()
    {
        $app = new Application(Application::ENV_TEST);
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
        $app = new Application(Application::ENV_TEST);
        $app['bridge.account'] = $this->getMockBuilder('Bridge_Account')
            ->disableOriginalConstructor()
            ->getMock();
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new BridgeExceptionSubscriber($app));
        $app->get('/', function () {
            throw new \InvalidArgumentException;
        });

        $client = new Client($app);

        // there is an exception thrown
        try {
            $this->fail('An exception should have been raised');
            $client->request('GET', '/');
        } catch(\Exception $e) {
        }
    }
}
