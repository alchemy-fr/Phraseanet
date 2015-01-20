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

use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

class TrustedProxySubscriber implements EventSubscriberInterface
{
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('setProxyConf', 0),
        );
    }

    public function setProxyConf(GetResponseEvent $event)
    {
        if (!$this->configuration->isSetup()) {
            return;
        }

        $proxies = isset($this->configuration['trusted-proxies']) ? $this->configuration['trusted-proxies'] : array();
        Request::setTrustedProxies($proxies);
    }
}
