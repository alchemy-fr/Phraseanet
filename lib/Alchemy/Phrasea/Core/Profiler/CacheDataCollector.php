<?php

namespace Alchemy\Phrasea\Core\Profiler;

use Alchemy\Phrasea\Core\Event\Subscriber\CacheStatisticsSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class CacheDataCollector implements DataCollectorInterface
{

    /**
     * @var CacheStatisticsSubscriber
     */
    private $statsListener;

    /**
     * @var CacheProfile
     */
    private $startProfile;

    /**
     * @var CacheProfile
     */
    private $endProfile;

    /**
     * @var CacheProfileSummary
     */
    private $summary;

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
        $this->startProfile = new CacheProfile($this->statsListener->getInitialStats() ?: []);
        $this->endProfile = new CacheProfile($this->statsListener->getCurrentStats() ?: []);

        $this->summary = new CacheProfileSummary(
            $this->statsListener->getCacheType(),
            $this->startProfile,
            $this->endProfile
        );
    }

    /**
     * @return CacheProfile
     */
    public function getInitialProfile()
    {
        return $this->startProfile;
    }

    /**
     * @return CacheProfile
     */
    public function getCurrentProfile()
    {
        return $this->endProfile;
    }

    /**
     * @return CacheProfileSummary
     */
    public function getSummary()
    {
        return $this->summary;
    }
}
