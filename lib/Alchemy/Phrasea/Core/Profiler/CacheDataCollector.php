<?php

namespace Alchemy\Phrasea\Core\Profiler;

use Alchemy\Phrasea\Core\Event\Subscriber\CacheStatisticsSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class CacheDataCollector implements DataCollectorInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var CacheStatisticsSubscriber
     */
    private $statsListener;

    /**
     * @param CacheStatisticsSubscriber $cacheStatisticsSubscriber
     */
    public function __construct(CacheStatisticsSubscriber $cacheStatisticsSubscriber)
    {
        $this->statsListener = $cacheStatisticsSubscriber;
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return 'cache';
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request $request A Request instance
     * @param Response $response A Response instance
     * @param \Exception $exception An Exception instance
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['early'] = $this->statsListener->getInitialStats();
        $this->data['late'] = $this->statsListener->getCurrentStats();
    }

    public function getData()
    {
        return $this->data;
    }

    public function getHits()
    {
        return $this->data['late']['hits'] - $this->data['early']['hits'];
    }

    public function getMisses()
    {
        return $this->data['late']['misses'] - $this->data['early']['misses'];
    }

    public function getTotalCalls()
    {
        return $this->getHits() + $this->getMisses();
    }
}
