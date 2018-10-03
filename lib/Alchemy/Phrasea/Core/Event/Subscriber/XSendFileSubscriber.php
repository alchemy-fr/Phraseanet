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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class XSendFileSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['applyHeaders', 0],
        ];
    }

    public function applyHeaders(GetResponseEvent $event)
    {
        if (!$this->app['configuration.store']->isSetup()) {
            return;
        }

        if ($this->app['phraseanet.xsendfile-factory']->isXSendFileModeEnabled()) {
            BinaryFileResponse::trustXSendfileTypeHeader();
            $this->app['phraseanet.xsendfile-factory']->getMode()->setHeaders($event->getRequest());
        }
    }
}
