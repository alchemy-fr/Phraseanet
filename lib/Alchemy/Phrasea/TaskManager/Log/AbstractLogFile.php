<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Log;

abstract class AbstractLogFile implements LogFileInterface
{
    /** @var string */
    protected $root;

    public function __construct($root)
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
    }

    /**
     * Returns the root of the task log files.
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $path = $this->getPath();
        if (is_file($path)) {
            return file_get_contents($this->getPath());
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getContentStream()
    {
        $path = $this->getPath();

        return function () use ($path) {
            $handle = fopen($path, 'r');
            while (!feof($handle)) {
                echo fread($handle, 4096);
                ob_flush();flush();
            }
            fclose($handle);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        file_put_contents($this->getPath(), '');
    }
}
