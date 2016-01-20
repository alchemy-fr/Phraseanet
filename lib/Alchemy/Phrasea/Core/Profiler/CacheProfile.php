<?php

namespace Alchemy\Phrasea\Core\Profiler;

class CacheProfile
{

    /**
     * @var array
     */
    private $serverStats;

    /**
     * @param array $serverStats
     */
    public function __construct(array $serverStats)
    {
        $this->serverStats = array_replace([
            'hits' => 0,
            'misses' => 0,
            'uptime' => 0,
            'memory_usage' => 0,
            'memory_available' => 0
        ], $serverStats);
    }

    /**
     * @return int
     */
    public function getHits()
    {
        return (int) $this->serverStats['hits'];
    }

    /**
     * @return int
     */
    public function getMisses()
    {
        return (int) $this->serverStats['misses'];
    }

    /**
     * @return int
     */
    public function getUptime()
    {
        return (int) $this->serverStats['uptime'];
    }

    /**
     * @return int
     */
    public function getMemUsage()
    {
        return (int) $this->serverStats['memory_usage'];
    }

    /**
     * @return int
     */
    public function getMemAvailable()
    {
        return (int) $this->serverStats['memory_available'];
    }

    /**
     * @return array
     */
    public function getServerStats()
    {
        return $this->serverStats;
    }
}
