<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Log;

interface LogFileInterface
{
    /**
     * Returns the path to the logfile.
     *
     * @return string[]
     */
    public function getLogFiles();

    /**
     * Returns the content of a logfile.
     *
     * @param string $logfile
     * @return string
     */
    public function getContent($logfile);

    /**
     * Streams the content of a logfile.
     *
     * This methods returns a closure that echoes the output.
     *
     * @param string $logfile
     * @return Closure
     */
    public function getContentStream($logfile);

    /**
     * Clears the content of a logfile.
     *
     * @param string $logfile
     */
    public function clear($logfile);
}
