<?php

namespace Alchemy\Phrasea\Core\Profiler;

class CacheProfileSummary
{
    /**
     * @var string
     */
    private $cacheType;

    /**
     * @var CacheProfile
     */
    private $initialProfile;

    /**
     * @var CacheProfile
     */
    private $finalProfile;

    /**
     * @param string $cacheType
     * @param CacheProfile $initialProfile
     * @param CacheProfile $finalProfile
     */
    public function __construct($cacheType, CacheProfile $initialProfile, CacheProfile $finalProfile)
    {
        $this->cacheType = (string) $cacheType;
        $this->initialProfile = $initialProfile;
        $this->finalProfile = $finalProfile;
    }

    public function getCacheType()
    {
        return $this->cacheType;
    }

    /**
     * @return int
     */
    public function getHits()
    {
        return (int) max(0, $this->finalProfile->getHits() - $this->initialProfile->getHits());
    }

    /**
     * @return int
     */
    public function getMisses()
    {
        return (int) max(0, $this->finalProfile->getMisses() - $this->initialProfile->getMisses());
    }

    /**
     * @return int
     */
    public function getCalls()
    {
        return $this->getHits() + $this->getMisses();
    }

    /**
     * @return float
     */
    public function getHitRatio()
    {
        $calls = $this->getCalls();

        if ($calls == 0) {
            return (float) 0;
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
            return (float) 0;
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
