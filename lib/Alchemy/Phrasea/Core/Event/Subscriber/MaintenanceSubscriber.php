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

class MaintenanceSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['checkForMaintenance', 0],
        ];
    }

    public function checkForMaintenance(GetResponseEvent $event)
    {
        if ($this->app['configuration.store']->isSetup() && $this->app['conf']->get(['main', 'maintenance'])) {
            $this->app->abort(503, 'Service Temporarily Unavailable', ['Retry-After' => 3600]);
        }
    }
}
