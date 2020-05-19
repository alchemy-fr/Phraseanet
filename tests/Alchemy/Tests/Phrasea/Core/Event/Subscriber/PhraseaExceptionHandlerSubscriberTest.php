<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\PhraseaExceptionHandler;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group functional
 * @group legacy
 */
class PhraseaExceptionHandlerSubscriberTest extends \PhraseanetTestCase
{
    public function testAResponseIsReturned()
    {
        $app = new Application(Application::ENV_TEST);
        $app['exception_handler'] = new PhraseaExceptionHandlerSubscriber(PhraseaExceptionHandler::register());
        $app->get('/', function () {
            throw new \Exception();
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }

    public function testANotFoundResponseIsReturned()
    {
        $app = new Application(Application::ENV_TEST);
        $app['exception_handler'] = new PhraseaExceptionHandlerSubscriber(PhraseaExceptionHandler::register());
        $app->get('/', function () {
            throw new NotFoundHttpException();
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testItCanBeDisabled()
    {
        $app = new Application(Application::ENV_TEST);
        $app['exception_handler'] = new PhraseaExceptionHandlerSubscriber(PhraseaExceptionHandler::register());
        $app->get('/', function () {
            throw new \Exception();
        });
        $app['exception_handler']->disable();

        $client = new Client($app);
        $this->setExpectedException(\Exception::class);
        $client->request('GET', '/');
    }
}
