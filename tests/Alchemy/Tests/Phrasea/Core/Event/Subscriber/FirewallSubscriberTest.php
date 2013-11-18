<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\FirewallSubscriber;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Client;

class FirewallSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testRedirection()
    {
        $app = new Application();
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
        $app = new Application();
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new FirewallSubscriber());
        $app->get('/', function () {
            throw new HttpException(500);
        });

        $client = new Client($app);
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException');
        $client->request('GET', '/');
    }
}
