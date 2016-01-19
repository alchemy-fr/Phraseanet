<?php

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CacheStatisticsSubscriber implements EventSubscriberInterface
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var array
     */
    private $stats = [];

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function getInitialStats()
    {
        return $this->stats;
    }

    public function getCurrentStats()
    {
        return $this->cache->getStats();
    }

    public function onKernelRequest()
    {
        $this->stats = $this->cache->getStats();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [ KernelEvents::REQUEST => [ 'onKernelRequest',  2048 ] ];
    }
}
