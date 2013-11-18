<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiOauth2ErrorsSubscriber;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Alchemy\Phrasea\Core\PhraseaExceptionHandler;

class ApiOauth2ErrorsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideExceptionsAndCode
     */
    public function testError($exception, $code, $contentType)
    {
        $app = new Application('test');
        $app['api'] = function () use ($app) {
            return new \API_V1_adapter($app);
        };
        $app->register(new \API_V1_Timer());
        $app['dispatcher']->addSubscriber(new ApiOauth2ErrorsSubscriber(PhraseaExceptionHandler::register()));
        $app->get('/api/oauthv2', function () use ($exception) {
            throw $exception;
        });

        $client = new Client($app);
        $client->request('GET', '/api/oauthv2');

        $this->assertEquals($code, $client->getResponse()->getStatusCode());
        $this->assertEquals($contentType, $client->getResponse()->headers->get('content-type'));
    }

    /**
     * @dataProvider provideExceptionsAndCode
     */
    public function testErrorOnOtherRoutes($exception, $code, $contentType)
    {
        $app = new Application('test');
        unset($app['exception_handler']);
        $app['api'] = function () use ($app) {
            return new \API_V1_adapter($app);
        };
        $app->register(new \API_V1_Timer());
        $app['dispatcher']->addSubscriber(new ApiOauth2ErrorsSubscriber(PhraseaExceptionHandler::register()));
        $app->get('/', function () use ($exception) {
            throw $exception;
        });

        $client = new Client($app);
        $this->setExpectedException(get_class($exception));
        $client->request('GET', '/');
    }

    public function provideExceptionsAndCode()
    {
        return [
            [new HttpException(512, null, null, ['content-type' => 'application/json']), 512, 'application/json'],
            [new HttpException(512, null, null), 512, 'text/html; charset=UTF-8'],
            [new \Exception(), 500, 'text/html; charset=UTF-8'],
        ];
    }
}
