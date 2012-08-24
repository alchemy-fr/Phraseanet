<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

class SearchEngineSuggestion
{
    private $query;
    private $suggestion;
    private $hits;

    public function __construct($query, $suggestion, $hits)
    {
        $this->query = $query;
        $this->suggestion = $suggestion;
        $this->hits = (int) $hits;
    }

    public function query()
    {
        return $this->query;
    }

    public function suggestion()
    {
        return $this->suggestion;
    }

    public function hits()
    {
        return $this->hits;
    }
}
