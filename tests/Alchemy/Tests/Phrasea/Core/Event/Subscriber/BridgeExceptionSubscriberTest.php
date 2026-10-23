<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Client;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BridgeExceptionSubscriberTest extends \PhraseanetTestCase
{
    public function testErrorOnBridgeExceptions()
    {
        $app = new Application(Application::ENV_TEST);
        $app['bridge.account'] = $this->createStub('Bridge_Account');
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
        $app['bridge.account'] = $this->createStub('Bridge_Account');
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new BridgeExceptionSubscriber($app));
        $app->get('/', function () {
            throw new \InvalidArgumentException;
        });

        $client = new Client($app);
        $this->expectException('\InvalidArgumentException');
        $client->request('GET', '/');
    }
}
