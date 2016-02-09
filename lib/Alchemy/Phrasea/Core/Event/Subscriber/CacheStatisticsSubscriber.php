<?php

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\Core\Profiler\TraceableCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CacheStatisticsSubscriber implements EventSubscriberInterface
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheType = '';

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
        $this->cacheType = $cache->getName();
    }

    public function getCacheNamespace()
    {
        if ($this->cache instanceof TraceableCache) {
            return $this->cache->getNamespace();
        }

        return '[ root ]';
    }

    public function getCallSummary()
    {
        if ($this->cache instanceof TraceableCache) {
            return $this->cache->getSummary();
        }

        return [
            'calls' => 0,
            'hits' => 0,
            'misses' => 0,
            'calls_by_type' => [],
            'calls_by_key' => []
        ];
    }

    public function getCalls()
    {
        if ($this->cache instanceof TraceableCache) {
            return $this->cache->getCalls();
        }

        return [];
    }

    public function getTimeSpent()
    {
        if ($this->cache instanceof TraceableCache) {
            return $this->cache->getTotalTime();
        }

        return 0;
    }

    public function getCacheType()
    {
        return $this->cacheType;
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
