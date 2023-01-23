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

use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\FacetsResponse;
use Doctrine\Common\Collections\ArrayCollection;

class SearchEngineResult
{
    protected $results;
    protected $queryText;
    protected $queryAST;
    protected $queryCompiled;
    protected $queryESLib;
    protected $duration;
    protected $offsetStart;
    protected $available;
    protected $total;
    protected $error;
    protected $warning;
    protected $suggestions;
    protected $propositions;
    protected $indexes;
    /** @var FacetsResponse */
    protected $facets;

    /**
     * @var SearchEngineOptions
     */
    private $options;

    /**
     * @param SearchEngineOptions $options
     * @param ArrayCollection $results
     * @param string $queryText
     * @param string $queryAST
     * @param string $queryCompiled
     * @param string $queryESLib
     * @param float $duration
     * @param int $offsetStart
     * @param int $available
     * @param int $total
     * @param mixed $error
     * @param mixed $warning
     * @param ArrayCollection $suggestions
     * @param Array $propositions
     * @param Array $indexes
     * @param FacetsResponse $facets
     */
    public function __construct(
        SearchEngineOptions $options,
        ArrayCollection $results,
        $queryText,
        $queryAST,
        $queryCompiled,
        $queryESLib,
        $duration,
        $offsetStart,
        $available,
        $total,
        $error,
        $warning,
        ArrayCollection $suggestions,
        $propositions,
        $indexes,
        FacetsResponse $facets = null
    ) {
        $this->options = $options;
        $this->results = $results;
        $this->queryText = $queryText;
        $this->queryAST = $queryAST;
        $this->queryCompiled = $queryCompiled;
        $this->queryESLib = $queryESLib;
        $this->duration = (float) $duration;
        $this->offsetStart = (int) $offsetStart;
        $this->available = (int) $available;
        $this->total = (int) $total;
        $this->error = $error;
        $this->warning = $warning;
        $this->suggestions = $suggestions;
        $this->propositions = $propositions;
        $this->indexes = $indexes;
        $this->facets = $facets;
    }

    /**
     * @return SearchEngineOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * An collection of results
     *
     * @return ArrayCollection|RecordInterface[]
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * The unparsed query related to these results
     *
     * @return string
     */
    public function getQueryText()
    {
        return $this->queryText;
    }


    public function getQueryAST()
    {
        return $this->queryAST;
    }

    public function getQueryCompiled()
    {
        return $this->queryCompiled;
    }

    public function getQueryESLib()
    {
        return $this->queryESLib;
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
     * @param  integer $amountPerPage
     * @return integer
     */
    public function getTotalPages($amountPerPage)
    {
        return ceil($this->total / $amountPerPage);
    }

    /**
     * Return the number of the current page depending on the amount displayed
     * on each page
     *
     * @param  integer $amountPerPage
     * @return integer
     */
    public function getCurrentPage($amountPerPage)
    {
        return max(1, ceil(($this->offsetStart + 1) / $amountPerPage));
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

    /**
     * @return array
     */
    public function getFacets()
    {
        return $this->facets->toArray();
    }
}
