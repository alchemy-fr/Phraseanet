<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\JsonRequestSubscriber;
use Symfony\Component\HttpKernel\Client;

class JsonRequestSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideRouteParameters
     */
    public function testRoutes($route, $isJson, $exceptionExpected)
    {
        $app = new Application();
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new JsonRequestSubscriber());
        $app->get($route, function () {
            throw new \Exception('I disagree');
        });

        $client = new Client($app);
        $headers = $isJson ? array('HTTP_ACCEPT' => 'application/json') : array();
        if ($exceptionExpected) {
            $this->setExpectedException('Exception');
        }
        $client->request('GET', $route, array(), array(), $headers);
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
        return array(
            array('/admin/status', true, true),
            array('/admin/collection/24', false, true),
            array('/admin/collection/24', true, false),
            array('/admin/databox/42', false, true),
            array('/admin/databox/42', true, false),
            array('/report', false, true),
            array('/report', true, false),
            array('/prod', false, true),
            array('/prod', true, false),
        );
    }
}
