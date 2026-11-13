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

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Silex\Application;

/**
 * Manages the route middlewares.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MiddlewareListener implements EventSubscriberInterface
{
    protected $app;

    /**
     * Constructor.
     *
     * @param Application $app An Application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Runs before filters.
     *
     * @param RequestEvent $event The event to handle
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        if (null === $routeName || !$route = $this->app['routes']->get($routeName)) {
            return;
        }

        foreach ((array) $route->getOption('_before_middlewares') as $callback) {
            $ret = call_user_func($this->app['callback_resolver']->resolveCallback($callback), $request, $this->app);
            if ($ret instanceof Response) {
                $event->setResponse($ret);

                return;
            } elseif (null !== $ret) {
                throw new \RuntimeException(sprintf('A before middleware for route "%s" returned an invalid response value. Must return null or an instance of Response.', $routeName));
            }
        }
    }

    /**
     * Runs after filters.
     *
     * @param ResponseEvent $event The event to handle
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        if (null === $routeName || !$route = $this->app['routes']->get($routeName)) {
            return;
        }

        foreach ((array) $route->getOption('_after_middlewares') as $callback) {
            $response = call_user_func($this->app['callback_resolver']->resolveCallback($callback), $request, $event->getResponse(), $this->app);
            if ($response instanceof Response) {
                $event->setResponse($response);
            } elseif (null !== $response) {
                throw new \RuntimeException(sprintf('An after middleware for route "%s" returned an invalid response value. Must return null or an instance of Response.', $routeName));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // this must be executed after the late events defined with before() (and their priority is -512)
            KernelEvents::REQUEST => ['onKernelRequest', -1024],
            KernelEvents::RESPONSE => ['onKernelResponse', 128],
        ];
    }
}
