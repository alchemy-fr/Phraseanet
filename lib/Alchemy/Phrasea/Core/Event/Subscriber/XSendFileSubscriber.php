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

use Silex\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class XSendFileSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('applyHeaders', 16),
        );
    }

    public function applyHeaders(GetResponseEvent $event)
    {
        if ($this->app['phraseanet.configuration']['xsendfile']['enable']) {
            $request = $event->getRequest();
            $request->headers->set('X-Sendfile-Type', 'X-Accel-Redirect');
            $request->headers->set('X-Accel-Mapping', (string) $this->app['phraseanet.xsendfile-mapping']);
        }
    }
}
