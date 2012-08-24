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

    public function __construct(ArrayCollection $results, $query, $duration, $offsetStart, $available, $total, $error, $warning, $suggestions, $propositions, $indexes)
    {
        $this->results = $results;
        $this->query = $query;
        $this->duration = (float) $duration;
        $this->offsetStart = (int) $offsetStart;
        $this->available = (int)$available;
        $this->total = (int)$total;
        $this->error = $error;
        $this->warning = $warning;
        $this->suggestions = $suggestions;
        $this->propositions = $propositions;
        $this->indexes = $indexes;

        return $this;
    }

    public function results()
    {
        return $this->results;
    }


    public function query()
    {
        return $this->query;
    }

    public function duration()
    {
        return $this->duration;
    }

    public function totalPages($amountPerPage)
    {
        return ceil($this->available / $amountPerPage);
    }

    public function currentPage($amountPerPage)
    {
        return ceil($this->offsetStart / $amountPerPage);
    }

    public function available()
    {
        return $this->available;
    }

    public function total()
    {
        return $this->total;
    }

    public function error()
    {
        return $this->error;
    }

    public function warning()
    {
        return $this->warning;
    }

    public function suggestions()
    {
        return $this->suggestions;
    }

    public function proposals()
    {
        return $this->propositions;
    }

    public function indexes()
    {
        return $this->indexes;
    }
}

