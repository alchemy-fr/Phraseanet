<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Configuration\SessionHandlerFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionHandlerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['session.storage.handler.factory'] = $app->share(function (Application $app) {
            return new SessionHandlerFactory($app['cache.connection-factory']);
        });
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if ($session && $session->isStarted()) {
            $session->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        // Priority should be lower than test session mock listener
        $app['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'), -129);
    }
}
