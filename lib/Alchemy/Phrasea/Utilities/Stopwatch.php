<?php


namespace Alchemy\Phrasea\Utilities;


class Stopwatch
{
    private $_start;

    public function __construct()
    {
        $this->reset();
    }
    private function reset()
    {
        $this->_start = microtime(true);
    }

    /**
     * returns the elapsed time in msec and reset
     *
     * @return float
     */
    public function getElapsedMilliseconds()
    {
        $e = (microtime(true) - $this->_start) * 1000.0 ;    // micro to milli
        $this->reset();

        return $e;
    }
}