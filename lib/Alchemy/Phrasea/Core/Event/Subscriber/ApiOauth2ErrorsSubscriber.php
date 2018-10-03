<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ApiOauth2ErrorsSubscriber implements EventSubscriberInterface
{
    private $handler;
    private $translator;

    public function __construct(ExceptionHandler $handler, TranslatorInterface $translator)
    {
        $this->handler = $handler;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onSilexError', 20],
        ];
    }

    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (0 !== strpos($request->getPathInfo(), '/api/oauthv2')) {
            return;
        }

        $e = $event->getException();

        $code = 500;
        $msg = $this->translator->trans('Whoops, looks like something went wrong.');
        $headers = [];

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();
            $msg = $e->getMessage();
            $code = $e->getStatusCode();
        }

        if (isset($headers['content-type']) && $headers['content-type'] == 'application/json') {
            $msg = json_encode(['msg'  => $msg, 'code' => $code]);
            $event->setResponse(new Response($msg, $code, $headers));
        } else {
            $response = $this->handler->createResponse($event->getException());
            $response->headers->set('Content-Type', 'text/html');
            $event->setResponse($response);
        }
    }
}
