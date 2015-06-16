<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Api\Result;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiExceptionHandlerSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onSilexError', 0],
        ];
    }

    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        $headers = [];
        $e = $event->getException();

        if ($e instanceof MethodNotAllowedHttpException) {
            $code = 405;
        } elseif ($e instanceof BadRequestHttpException) {
            $code = 400;
        } elseif ($e instanceof AccessDeniedHttpException) {
            $code = 403;
        } elseif ($e instanceof UnauthorizedHttpException) {
            $code = 401;
        } elseif ($e instanceof NotFoundHttpException) {
            $code = 404;
        } elseif ($e instanceof HttpExceptionInterface) {
            if (in_array($e->getStatusCode(), [400, 401, 403, 404, 405, 406, 503])) {
                $code = $e->getStatusCode();
            } else {
                $code = 500;
            }
        } else {
            $code = 500;
        }

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();
        }

        $response = Result::createError($event->getRequest(), $code, $e->getMessage())->createResponse();
        $response->headers->set('X-Status-Code', $response->getStatusCode());

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        $event->setResponse($response);
    }
}
