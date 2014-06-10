<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiExceptionHandlerSubscriberTest extends \PHPUnit_Framework_TestCase
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
    }

    public function provideExceptionsAndCode()
    {
        return array(
            array(new \API_V1_exception_methodnotallowed(), 405),
            array(new MethodNotAllowedHttpException(array('PUT', 'HEAD')), 405),
            array(new \API_V1_exception_badrequest(), 400),
            array(new \API_V1_exception_forbidden(), 403),
            array(new \API_V1_exception_unauthorized(), 401),
            array(new \API_V1_exception_internalservererror(), 500),
            array(new NotFoundHttpException(), 404),
            array(new \Exception(), 500),
        );
    }
}
