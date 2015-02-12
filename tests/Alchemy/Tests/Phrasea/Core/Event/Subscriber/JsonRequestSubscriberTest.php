<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\JsonRequestSubscriber;
use Symfony\Component\HttpKernel\Client;

class JsonRequestSubscriberTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideRouteParameters
     */
    public function testRoutes($route, $isJson, $exceptionExpected)
    {
        $app = new Application(Application::ENV_TEST);
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new JsonRequestSubscriber());
        $app->get($route, function () {
            throw new \Exception('I disagree');
        });

        $client = new Client($app);
        $headers = $isJson ? ['HTTP_ACCEPT' => 'application/json'] : [];
        if ($exceptionExpected) {
            $this->setExpectedException('Exception');
        }
        $client->request('GET', $route, [], [], $headers);
        if (!$exceptionExpected) {
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('success', $data);
            $this->assertArrayHasKey('message', $data);
            $this->assertFalse($data['success']);
        }
    }

    public function provideRouteParameters()
    {
        return [
            ['/admin/status', true, true],
            ['/admin/collection/24', false, true],
            ['/admin/collection/24', true, false],
            ['/admin/databox/42', false, true],
            ['/admin/databox/42', true, false],
            ['/report', false, true],
            ['/report', true, false],
            ['/prod', false, true],
            ['/prod', true, false],
        ];
    }
}
