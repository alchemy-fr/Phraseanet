<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ContentNegotiationSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', Application::EARLY_EVENT),
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $priorities = array('text/html', 'application/json',  '*/*');
        $format = $this->app['format.negociator']->getBest($event->getRequest()->headers->get('accept', '*/*'), $priorities);

        if (null === $format) {
            $this->app->abort(406, 'Not acceptable');
        }

        $event->getRequest()->setRequestFormat($event->getRequest()->getFormat($format->getValue()));
    }
}
