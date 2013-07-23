<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class DebuggerSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(
                array('checkIp', 255),
            ),
        );
    }

    public function checkIp(GetResponseEvent $event)
    {
        if (Application::ENV_DEV !== $this->app->getEnvironment()) {
            return;
        }

        if ($this->app['phraseanet.configuration']->isSetup()
            && isset($this->app['phraseanet.configuration']['debugger'])
            && isset($this->app['phraseanet.configuration']['debugger']['allowed-ips'])) {

            $allowedIps = $this->app['phraseanet.configuration']['debugger']['allowed-ips'];
            $allowedIps = is_array($allowedIps) ? $allowedIps : array($allowedIps);
        } else {
            $allowedIps = array();
        }

        $ips = array_merge(array('127.0.0.1', 'fe80::1', '::1'), $allowedIps);

        if (!in_array($event->getRequest()->getClientIp(), $ips)) {
            throw new AccessDeniedHttpException('You are not allowed to access this file. Check index_dev.php for more information.');
        }
    }
}
