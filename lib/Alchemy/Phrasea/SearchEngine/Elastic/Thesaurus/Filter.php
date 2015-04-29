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

class Filter
{
    private $paths;

    public static function childOfConcepts(array $concepts)
    {
        return new self(Concept::toPathArray($concepts));
    }

    public static function byDatabox($databox_id)
    {
        return new self(array(sprintf('/%d', $databox_id)));
    }

    public static function dumpPaths(Filter $filter)
    {
        return $filter->paths;
    }

    private function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function getQueryFilter()
    {
        $filter = array();
        $filter['terms']['path'] = $this->paths;

        return $filter;
    }
}
