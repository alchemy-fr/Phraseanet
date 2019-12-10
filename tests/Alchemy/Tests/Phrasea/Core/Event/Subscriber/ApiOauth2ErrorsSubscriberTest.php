<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiOauth2ErrorsSubscriber;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Alchemy\Phrasea\Core\PhraseaExceptionHandler;
use Alchemy\Tests\Tools\TranslatorMockTrait;

/**
 * @group functional
 * @group legacy
 */
class ApiOauth2ErrorsSubscriberTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;
    /**
     * @dataProvider provideExceptionsAndCode
     */
    public function testError($exception, $code, $contentType)
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new ApiOauth2ErrorsSubscriber(PhraseaExceptionHandler::register(), $this->createTranslatorMock()));
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
        $app = new Application(Application::ENV_TEST);
        unset($app['exception_handler']);
        $app['dispatcher']->addSubscriber(new ApiOauth2ErrorsSubscriber(PhraseaExceptionHandler::register(), $this->createTranslatorMock()));
        $app->get('/', function () use ($exception) {
            throw $exception;
        });

        $client = new Client($app);

        // there is an exception thrown
        try {
            $this->fail('An exception should have been raised');
            $client->request('GET', '/');
        } catch(\Exception $e) {
        }

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
