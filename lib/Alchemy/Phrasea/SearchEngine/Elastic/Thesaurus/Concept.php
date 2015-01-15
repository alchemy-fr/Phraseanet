<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;

class Concept
{
    private $path;

    public function __construct($path)
    {
        $this->path = (string) $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->path;
    }

    public static function toPathArray(array $concepts)
    {
        foreach ($concepts as $index => $concept) {
            $concepts[$index] = $concept->getPath();
        }
        return $concepts;
    }
}
