<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

abstract class AbstractServerMode
{
    protected $mapping = [];

    /**
     * @params array $mapping
     *
     * @throws InvalidArgumentException if mapping is invalid;
     */
    public function __construct(array $mapping)
    {
        $this->setMapping($mapping);
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @params array $mapping
     *
     * @throws InvalidArgumentException if mapping is invalid;
     */
    abstract public function setMapping(array $mapping);

    /**
     * Sanitizes path directory.
     *
     * @param string $path
     *
     * @return string
     */
    protected function sanitizePath($path)
    {
        return sprintf('/%s', trim($path, '/'));
    }

    /**
     * Sanitizes a mount point.
     *
     * @param string $mountPoint
     *
     * @return string
     */
    protected function sanitizeMountPoint($mountPoint)
    {
        return sprintf('/%s', trim($mountPoint, '/'));
    }
}
