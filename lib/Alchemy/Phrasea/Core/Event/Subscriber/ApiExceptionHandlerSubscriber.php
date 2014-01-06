<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
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

        if ($e instanceof \API_V1_exception_methodnotallowed) {
            $code = \API_V1_result::ERROR_METHODNOTALLOWED;
        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $code = \API_V1_result::ERROR_METHODNOTALLOWED;
        } elseif ($e instanceof BadRequestHttpException) {
            $code = \API_V1_result::ERROR_BAD_REQUEST;
        } elseif ($e instanceof \API_V1_exception_badrequest) {
            $code = \API_V1_result::ERROR_BAD_REQUEST;
        } elseif ($e instanceof \API_V1_exception_forbidden) {
            $code = \API_V1_result::ERROR_FORBIDDEN;
        } elseif ($e instanceof \API_V1_exception_unauthorized) {
            $code = \API_V1_result::ERROR_UNAUTHORIZED;
        } elseif ($e instanceof \API_V1_exception_internalservererror) {
            $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
        } elseif ($e instanceof AccessDeniedHttpException) {
            $code = \API_V1_result::ERROR_FORBIDDEN;
        } elseif ($e instanceof UnauthorizedHttpException) {
            $code = \API_V1_result::ERROR_UNAUTHORIZED;
        } elseif ($e instanceof NotFoundHttpException) {
            $code = \API_V1_result::ERROR_NOTFOUND;
        } elseif ($e instanceof HttpExceptionInterface) {
            if (503 === $e->getStatusCode()) {
                $code = \API_V1_result::ERROR_MAINTENANCE;
            } else {
                $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
            }
        } else {
            $code = \API_V1_result::ERROR_INTERNALSERVERERROR;
        }

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();
        }

        $result = $this->app['api']->get_error_message($event->getRequest(), $code, $e->getMessage());
        $response = $result->get_response();
        $response->headers->set('X-Status-Code', $result->get_http_code());

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        $event->setResponse($response);
    }
}
