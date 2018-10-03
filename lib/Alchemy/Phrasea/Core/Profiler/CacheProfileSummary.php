<?php

namespace Alchemy\Phrasea\Core\Profiler;

class CacheProfileSummary
{
    /**
     * @var string
     */
    private $cacheType;

    /**
     * @var string
     */
    private $cacheNamespace;

    /**
     * @var CacheProfile
     */
    private $initialProfile;

    /**
     * @var CacheProfile
     */
    private $finalProfile;

    /**
     * @var array
     */
    private $callSummaryData;

    /**
     * @param string $cacheType
     * @param string $namespace
     * @param CacheProfile $initialProfile
     * @param CacheProfile $finalProfile
     * @param array $callSummaryData
     */
    public function __construct(
        $cacheType,
        $namespace,
        CacheProfile $initialProfile,
        CacheProfile $finalProfile,
        array $callSummaryData
    ) {
        $this->cacheType = (string)$cacheType;
        $this->cacheNamespace = (string)$namespace;
        $this->initialProfile = $initialProfile;
        $this->finalProfile = $finalProfile;
        $this->callSummaryData = $callSummaryData;
    }

    /**
     * @return string
     */
    public function getCacheType()
    {
        return $this->cacheType;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->cacheNamespace;
    }

    /**
     * @return int
     */
    public function getHits()
    {
        if (isset($this->callSummaryData['hits'])) {
            return (int) $this->callSummaryData['hits'];
        }

        return (int)max(0, $this->finalProfile->getHits() - $this->initialProfile->getHits());
    }

    /**
     * @return int
     */
    public function getMisses()
    {
        if (isset($this->callSummaryData['misses'])) {
            return (int) $this->callSummaryData['misses'];
        }

        return (int)max(0, $this->finalProfile->getMisses() - $this->initialProfile->getMisses());
    }

    /**
     * @return int
     */
    public function getCalls()
    {
        if (isset($this->callSummaryData['calls'])) {
            return (int) $this->callSummaryData['calls'];
        }

        return $this->getHits() + $this->getMisses();
    }

    /**
     * @return float
     */
    public function getHitRatio()
    {
        $calls = $this->getCalls();

        if ($calls == 0) {
            return (float)0;
        }

        return $this->getHits() / $calls;
    }

    /**
     * @return float
     */
    public function getMissRatio()
    {
        $calls = $this->getCalls();

        if ($calls == 0) {
            return (float)0;
        }

        return $this->getMisses() / $calls;
    }

    /**
     * @return int
     */
    public function getMemUsageDelta()
    {
        return $this->finalProfile->getMemUsage() - $this->initialProfile->getMemUsage();
    }

    /**
     * @return int
     */
    public function getMemAvailableDelta()
    {
        return $this->finalProfile->getMemAvailable() - $this->initialProfile->getMemAvailable();
    }

    public function getUptimeDelta()
    {
        return $this->finalProfile->getUptime() - $this->initialProfile->getUptime();
    }
}
