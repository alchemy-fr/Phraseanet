<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Nginx extends AbstractStaticMode implements StaticFileModeInterface
{
    /**
     * @params array $mapping
     *
     * @throws InvalidArgumentException if mapping is invalid;
     */
    public function setMapping(array $mapping)
    {
        if (!isset($mapping['directory'])) {
            throw new InvalidArgumentException('Static file mapping entry must contain at least a "directory" key');
        }

        if (!isset($mapping['mount-point'])) {
            throw new InvalidArgumentException('Static file mapping entry must contain at least a "mount-point" key');
        }

        $this->mapping = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualHostConfiguration()
    {
        $output = "\n";
        $output .= "    location " . $this->mapping['mount-point']. " {\n";
        $output .= "        alias ".$this->mapping['directory'].";\n";
        $output .= "    }\n";

        return $output;
    }
}
