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
    public function getResults()
    {
        return $this->results;
    }

    /**
     * The query related to these results
     * 
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * The duration of the query
     * 
     * @return float
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Return the number of page depending on the amount displayed on each page
     * 
     * @param integer   $amountPerPage
     * @return integer
     */
    public function getTotalPages($amountPerPage)
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
    public function getCurrentPage($amountPerPage)
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
    public function getAvailable()
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
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Return an error message returned by the search engine
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Return a warning message returned by the search engine
     * 
     * @return string
     */
    public function getWarning()
    {
        return $this->warning;
    }

    /**
     * Return a collection of SearchEngineSuggestion
     * 
     * @return ArrayCollection
     */
    public function getSuggestions()
    {
        return $this->suggestions;
    }

    /**
     * Return HTML proposals
     * 
     * @return string
     */
    public function getProposals()
    {
        return $this->propositions;
    }

    /**
     * Return the index name where the query happened
     * 
     * @return string
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
}

