<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ConfigurationLoaderSubscriber implements EventSubscriberInterface
{
    private $configuration;

    public function __construct(HostConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['loadConfiguration', 255],
            ],
            KernelEvents::FINISH_REQUEST => [
                ['unloadConfiguration', 255],
            ],
        ];
    }

    public function loadConfiguration(RequestEvent $event)
    {
        $this->configuration->setHost($event->getRequest()->getHost());
    }

    public function unloadConfiguration(FinishRequestEvent $event)
    {
        $this->configuration->setHost(null);
    }
}
