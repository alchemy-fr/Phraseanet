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

    /**
     * @var string 
     */
    private $query;

    /**
     * @var string 
     */
    private $suggestion;

    /**
     * @var int 
     */
    private $hits;

    public function __construct($query, $suggestion, $hits)
    {
        $this->query = $query;
        $this->suggestion = $suggestion;
        $this->hits = (int) $hits;
    }

    /**
     * The query related to the suggestion
     * 
     * @return string
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * The actual suggestion
     * 
     * @return string
     */
    public function suggestion()
    {
        return $this->suggestion;
    }

    /**
     * The number of hits
     * 
     * @return int
     */
    public function hits()
    {
        return $this->hits;
    }

}
