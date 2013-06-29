<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class XsendfileMapping
{
    private $mapping;

    /**
     * @param array $mapping
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $mapping)
    {
        $this->validate($mapping);

        $final = array();

        foreach($mapping as $entry) {
            if (!is_dir(trim($entry['directory'])) || '' === trim($entry['mount-point'])) {
                continue;
            }

            $entry = array(
                'directory' => $this->sanitizePath(realpath($entry['directory'])),
                'mount-point' => $this->sanitizeMountPoint($entry['mount-point']),
            );

            $final[] = $entry;
        }

        $this->mapping = $final;
    }

    public function __toString()
    {
        $final = array();

        foreach($this->mapping as $entry) {
            $final[] = sprintf('%s=%s', $entry['mount-point'], $entry['directory']);
        }

        return implode(',', $final);
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function sanitizePath($path)
    {
        return sprintf('/%s', trim($path, '/'));
    }

    public function sanitizeMountPoint($mountPoint)
    {
        return sprintf('/%s', trim($mountPoint, '/'));
    }

    private function validate(array $mapping)
    {
        foreach ($mapping as $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException('XSendFile mapping entry must be an array');
            }

            if (!isset($entry['directory']) || !isset($entry['mount-point'])) {
                throw new InvalidArgumentException('XSendFile mapping entry must contain at least two keys "directory" and "mount-point"');
            }
        }
    }
}
