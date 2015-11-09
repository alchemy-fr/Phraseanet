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

use Negotiation\Accept;
use Negotiation\Negotiator;
use Silex\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ContentNegotiationSubscriber implements EventSubscriberInterface
{
    /** @var Negotiator */
    private $negotiator;
    /** @var array */
    private $priorities;

    public function __construct(Negotiator $negotiator, array $priorities)
    {
        $this->negotiator = $negotiator;
        $this->priorities = $priorities;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', Application::EARLY_EVENT),
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $format = $this->negotiator->getBest($event->getRequest()->headers->get('accept', '*/*'), $this->priorities);

        if (!$format instanceof Accept) {
            throw new HttpException(406);
        }

        $event->getRequest()->setRequestFormat($event->getRequest()->getFormat($format->getValue()));
    }
}
