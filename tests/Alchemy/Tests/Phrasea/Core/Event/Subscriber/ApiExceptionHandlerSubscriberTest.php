<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiExceptionHandlerSubscriber;
use Symfony\Component\HttpKernel\HttpKernelBrowser as Client;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ApiExceptionHandlerSubscriberTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideExceptionsAndCode')]
    public function testError($exception, $code)
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new ApiExceptionHandlerSubscriber());
        $app->get('/', function () use ($exception) {
            throw $exception;
        });

        $client = new Client($app);
        $client->request('GET', '/');

        $this->assertEquals($code, $client->getResponse()->getStatusCode());
    }

    public static function provideExceptionsAndCode()
    {
        return [
            [new MethodNotAllowedHttpException(['PUT', 'HEAD']), 405],
            [new NotFoundHttpException(), 404],
            [new BadRequestHttpException(), 400],
            [new AccessDeniedHttpException(), 403],
            [new UnauthorizedHttpException('challenge'), 401],
            [new \Exception(), 500],
        ];
    }
}
