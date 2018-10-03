<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        $this->hits = null !== $hits ? (int) $hits : null;
    }

    /**
     * The query related to the suggestion
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * The actual suggestion
     *
     * @return string
     */
    public function getSuggestion()
    {
        return $this->suggestion;
    }

    /**
     * The number of hits
     *
     * @return int
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Returns the suggestion as an array representation.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'suggestion' => $this->getSuggestion(),
            'query' => $this->getQuery(),
            'hits'  => $this->getHits(),
        ];
    }
}
