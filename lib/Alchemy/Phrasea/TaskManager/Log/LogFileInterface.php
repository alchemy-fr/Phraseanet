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
     * @return string
     */
    public function getPath();

    /**
     * Returns the content of the logfile.
     *
     * @return string
     */
    public function getContent();

    /**
     * Streams the content of the logfile.
     *
     * This methods returns a closure that echoes the output.
     *
     * @return Closure
     */
    public function getContentStream();

    /**
     * Clears the content of the logfile.
     */
    public function clear();
}
