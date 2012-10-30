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

use Doctrine\Common\Collections\ArrayCollection;

class SearchEngineResult
{
    protected $results;
    protected $query;
    protected $duration;
    protected $offsetStart;
    protected $available;
    protected $total;
    protected $error;
    protected $warning;
    protected $suggestions;
    protected $propositions;
    protected $indexes;

    public function __construct(ArrayCollection $results, $query, $duration, $offsetStart, $available, $total, $error, $warning, ArrayCollection $suggestions, $propositions, $indexes)
    {
        $this->results = $results;
        $this->query = $query;
        $this->duration = (float) $duration;
        $this->offsetStart = (int) $offsetStart;
        $this->available = (int) $available;
        $this->total = (int) $total;
        $this->error = $error;
        $this->warning = $warning;
        $this->suggestions = $suggestions;
        $this->propositions = $propositions;
        $this->indexes = $indexes;

        return $this;
    }

    /**
     * An collection of results
     * 
     * @return ArrayCollection
     */
    public function results()
    {
        return $this->results;
    }

    /**
     * The query related to these results
     * 
     * @return string
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * The duration of the query
     * 
     * @return float
     */
    public function duration()
    {
        return $this->duration;
    }

    /**
     * Return the number of page depending on the amount displayed on each page
     * 
     * @param integer   $amountPerPage
     * @return integer
     */
    public function totalPages($amountPerPage)
    {
        return ceil($this->available / $amountPerPage);
    }

    /**
     * Return the number of the current page depending on the amount displayed 
     * on each page
     * 
     * @param integer   $amountPerPage
     * @return integer
     */
    public function currentPage($amountPerPage)
    {
        return ceil($this->offsetStart / $amountPerPage);
    }

    /**
     * Return the number of results that can be returned by the search engine
     * 
     * The difference with 'total' is that this method return the actual number
     * of results that can be fetched whereas 'total' returns the number of 
     * results that matches the query (can be greater than available quantity)
     * 
     * @return int
     */
    public function available()
    {
        return $this->available;
    }

    /**
     * Return the number of items that match the query. Some items may be not
     * retrievable. To get the number of results that can be retrieved, use
     * the 'available' method
     * 
     * @return int
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * Return an error message returned by the search engine
     * 
     * @return string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Return a warning message returned by the search engine
     * 
     * @return string
     */
    public function warning()
    {
        return $this->warning;
    }

    /**
     * Return a collection of SearchEngineSuggestion
     * 
     * @return ArrayCollection
     */
    public function suggestions()
    {
        return $this->suggestions;
    }

    /**
     * Return HTML proposals
     * 
     * @return string
     */
    public function proposals()
    {
        return $this->propositions;
    }

    /**
     * Return the index name where the query happened
     * 
     * @return string
     */
    public function indexes()
    {
        return $this->indexes;
    }
}

