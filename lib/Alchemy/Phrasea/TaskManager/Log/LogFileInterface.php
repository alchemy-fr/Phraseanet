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
     * Returns versions (suffixes due to logrotate) of the logfiles.
     * ex. foo.log --> ""
     *     bar-2015-12-25.log --> "2015-12-25"
     *
     * @return string[]
     */
    public function getVersions();

    /**
     * Returns the path of a logfile.
     *
     * @param string $version
     * @return string
     */
    public function getPath($version = '');

    /**
     * Returns the content of a logfile.
     *
     * @param string $version
     * @return string
     */
    public function getContent($version = '');

    /**
     * Streams the content of a logfile.
     *
     * This methods returns a closure that echoes the output.
     *
     * @param string $version
     * @return Closure
     */
    public function getContentStream($version = '');

    /**
     * Clears the content of a logfile.
     *
     * @param string $version
     */
    public function clear($version = '');

    /**
     * Returns true if the logfile exists
     *
     * @param $version
     * @return bool
     */
    public function versionExists($version);

}
