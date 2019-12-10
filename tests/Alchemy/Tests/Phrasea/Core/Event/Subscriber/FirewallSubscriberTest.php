<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\FirewallSubscriber;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 */
class FirewallSubscriberTest extends \PhraseanetTestCase
{
    public function testRedirection()
    {
        $app = new Application(Application::ENV_TEST);
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new FirewallSubscriber());
        $app->get('/', function () {
            throw new HttpException(500, null, null, ['X-Phraseanet-Redirect' => '/hello-world']);
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/hello-world', $client->getResponse()->headers->get('Location'));
    }

    public function testNoHeaderNoRedirection()
    {
        $app = new Application(Application::ENV_TEST);
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new FirewallSubscriber());
        $app->get('/', function () {
            throw new HttpException(500);
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
