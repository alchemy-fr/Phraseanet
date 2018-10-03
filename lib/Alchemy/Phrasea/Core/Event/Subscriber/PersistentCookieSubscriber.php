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

use Silex\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class PersistentCookieSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['checkPersistentCookie', 128],
        ];
    }

    public function checkPersistentCookie(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->app['configuration.store']->isSetup() && $request->cookies->has('persistent') && !$this->app->getAuthenticator()->isAuthenticated()) {
            if (false !== $session = $this->app['authentication.persistent-manager']->getSession($request->cookies->get('persistent'))) {
                $this->app->getAuthenticator()->refreshAccount($session);
            }
        }
    }
}
