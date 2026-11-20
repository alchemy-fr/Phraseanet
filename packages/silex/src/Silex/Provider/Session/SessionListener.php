<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider\Session;

use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

/**
 * Sets the session in the request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SessionListener extends AbstractSessionListener
{
    private $app;

    public function __construct(Container $app)
    {
        $this->app = $app;

        parent::__construct(new PsrContainer($app), $app['debug'] ?? false, $app['session.storage.options'] ?? []);
    }

    /**
     * The application session service is the request session, so that it is handled
     * (saved, cookie sent) at the end of the request even when only used through the container.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() && !$event->getRequest()->hasSession() && null !== $session = $this->getSession()) {
            $event->getRequest()->setSession($session);
        }

        parent::onKernelRequest($event);
    }

    protected function getSession(): ?SessionInterface
    {
        if (!isset($this->app['session'])) {
            return null;
        }

        return $this->app['session'];
    }
}
