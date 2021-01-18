<?php


namespace Alchemy\Phrasea\Utilities;


class Stopwatch
{
    private $_start;
    private $_last;
    private $_stop = null;  // set when stopped
    private $_lapses;
    private $_name;

    public function __construct($name)
    {
        $this->_name = $name;
        $this->_lapses = [];
        $this->_start = $this->_last = microtime(true);
    }

    public function stop()
    {
        if($this->_stop === null) {
            $this->_stop = microtime(true);
        }
    }

    /**
     * returns the elapsed duration since creation, in msec
     * @return float
     */
    public function getDuration()
    {
        $this->stop();
        return ($this->_stop - $this->_start) * 1000.0;
    }

    /**
     * "snapshot" the intermediate duration to allow to get stats on code
     * nb : each call resets the intermediate timer, so the total duration is the SUM of all lapses
     *
     * @param string $snapname
     *
     * @return float The last lap ( = duration since last call, or since start)
     */
    public function lap($snapname = '')
    {
        $e = (($now = microtime(true)) - $this->_last) * 1000.0 ;    // micro to milli
        $this->_last = $now;

        if($snapname) {
            $this->_lapses[$snapname] = $e;
        }

        return $e;
    }

    /**
     * returns all the lapses (= durations), including a "_total"
     * @return array
     */
    public function getLapses()
    {
        $this->stop();
        $this->_lapses['_total'] = ($this->_stop - $this->_start) * 1000.0;
        return $this->_lapses;
    }

    public function log()
    {
//         file_put_contents('/var/alchemy/Phraseanet/tmp/phraseanet.log',
//            $this->_name . "\n" . var_export($this->getLapses(), true) . "\n\n",
//            FILE_APPEND
//         );
    }

    /**
     * return all lapses as a "Server-Timing" header value
     *
     * @return string
     */
    public function getLapsesAsServerTimingHeader()
    {
        $this->stop();
        $this->_lapses['_total'] = ($this->_stop - $this->_start) * 1000.0;
        $t = [];
        $i = 0;
        $m = count($this->_lapses) < 11 ? -1 : 10;  // tricky test parameter to format $i with 1 or 2 digits
        array_walk(
            $this->_lapses,
            function(&$v, $k) use (&$t, &$i, $m) {
                // chrome displays values sorted by name, so we prefix with 2 digits to keep execution order
                $t[] = '' . $this->_name . ';desc="' . $this->_name . '.' . ($i<$m?'0':'') . $i++ . '.'. $k . '";' . 'dur=' . $v;
            }
        );

        return  join(',', $t);
    }
}