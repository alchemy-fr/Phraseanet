<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiExceptionHandlerSubscriberTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideExceptionsAndCode
     */
    public function testError($exception, $code)
    {
        $app = new Application('test');
        $app['api'] = function () use ($app) {
            return new \API_V1_adapter($app);
        };
        $app->register(new \API_V1_Timer());
        $app['dispatcher']->addSubscriber(new ApiExceptionHandlerSubscriber($app));
        $app->get('/', function () use ($exception) {
            throw $exception;
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals($code, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('content-type'));
    }

    public function provideExceptionsAndCode()
    {
        return [
            [new \API_V1_exception_methodnotallowed(), 405],
            [new MethodNotAllowedHttpException(['PUT', 'HEAD']), 405],
            [new \API_V1_exception_badrequest(), 400],
            [new \API_V1_exception_forbidden(), 403],
            [new \API_V1_exception_unauthorized(), 401],
            [new \API_V1_exception_internalservererror(), 500],
            [new NotFoundHttpException(), 404],
            [new \Exception(), 500],
        ];
    }
}
