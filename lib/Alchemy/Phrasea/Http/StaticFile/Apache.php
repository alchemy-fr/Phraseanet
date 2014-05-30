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

class Apache extends AbstractStaticMode implements StaticFileModeInterface
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
        $output .= "    Alias ".$this->mapping['mount-point']." ".$this->mapping['directory']."\n";
        $output .= "\n";
        $output .= "    <Location ".$this->mapping['directory'].">\n";
        $output .= "        Order allow,deny\n";
        $output .= "        Allow from all\n";
        $output .= "    </Location>\n";
        $output .= "\n";

        return $output;
    }
}
