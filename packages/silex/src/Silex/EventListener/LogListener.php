<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Logs request, response, and exceptions.
 */
class LogListener implements EventSubscriberInterface
{
    protected $logger;
    protected $exceptionLogFilter;

    public function __construct(LoggerInterface $logger, $exceptionLogFilter = null)
    {
        $this->logger = $logger;
        if (null === $exceptionLogFilter) {
            $exceptionLogFilter = function (\Throwable $e) {
                if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                    return LogLevel::ERROR;
                }

                return LogLevel::CRITICAL;
            };
        }

        $this->exceptionLogFilter = $exceptionLogFilter;
    }

    /**
     * Logs master requests on event KernelEvents::REQUEST.
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->logRequest($event->getRequest());
    }

    /**
     * Logs master response on event KernelEvents::RESPONSE.
     *
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->logResponse($event->getResponse());
    }

    /**
     * Logs uncaught exceptions on event KernelEvents::EXCEPTION.
     *
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $this->logException($event->getThrowable());
    }

    /**
     * Logs a request.
     *
     * @param Request $request
     */
    protected function logRequest(Request $request)
    {
        $this->logger->log(LogLevel::DEBUG, '> '.$request->getMethod().' '.$request->getRequestUri());
    }

    /**
     * Logs a response.
     *
     * @param Response $response
     */
    protected function logResponse(Response $response)
    {
        $message = '< '.$response->getStatusCode();

        if ($response instanceof RedirectResponse) {
            $message .= ' '.$response->getTargetUrl();
        }

        $this->logger->log(LogLevel::DEBUG, $message);
    }

    /**
     * Logs an exception.
     */
    protected function logException(\Throwable $e)
    {
        $this->logger->log(call_user_func($this->exceptionLogFilter, $e), sprintf('%s: %s (uncaught exception) at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()), ['exception' => $e]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
            /*
             * Priority -4 is used to come after those from SecurityServiceProvider (0)
             * but before the error handlers added with Silex\Application::error (defaults to -8)
             */
            KernelEvents::EXCEPTION => ['onKernelException', -4],
        ];
    }
}
