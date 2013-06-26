<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\XSendFile;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Mapping
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
        $this->mapping = $mapping;
    }

    public function __toString()
    {
        $final = array();

        foreach($this->mapping as $entry) {
            if (!is_dir($entry['directory']) || '' === $entry['mount-point']) {
                continue;
            }

            $final[] = sprintf('%s=%s', $this->sanitizeMountPoint($entry['mount-point']), $this->sanitizePath(realpath($entry['directory'])));
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
        foreach($mapping as $entry) {
            if (!is_array($entry)) {
                throw new InvalidArgumentException('XSendFile mapping entry must be an array');
            }

            if (!isset($entry['directory']) && !isset($entry['mount-point'])) {
                throw new InvalidArgumentException('XSendFile mapping entry must contain at least two keys "directory" and "mounbt-point"');
            }
        }
    }
}
