<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;

class SearchEngineResultTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineResult
     */
    public function testBasic()
    {
        $results = new ArrayCollection([
                    self::$DI['record_2']
                ]);

        $query = 'Gotainer';
        $duration = 1 / 3;
        $offsetStart = 23;
        $available = 25;
        $total = 10000;
        $error = 'this is an error message';
        $warning = 'this is a warning message';
        $suggestions = new ArrayCollection([
                        new SearchEngineSuggestion($query, 'Richard', 22)
        ]);
        $propositions = new ArrayCollection();
        $indexes = 'new-index';

        $result = new SearchEngineResult($results, $query, $duration,
                        $offsetStart, $available, $total, $error, $warning,
                        $suggestions, $propositions, $indexes);

        $this->assertEquals($warning, $result->getWarning());
        $this->assertEquals(2, $result->getTotalPages(23));
        $this->assertEquals(5, $result->getTotalPages(5));
        $this->assertEquals($total, $result->getTotal());
        $this->assertEquals($suggestions, $result->getSuggestions());
        $this->assertEquals($results, $result->getResults());
        $this->assertEquals($query, $result->getQuery());
        $this->assertEquals($propositions, $result->getProposals());
        $this->assertEquals($indexes, $result->getIndexes());
        $this->assertEquals($error, $result->getError());
        $this->assertEquals($duration, $result->getDuration());
        $this->assertEquals(2, $result->getCurrentPage(23));
        $this->assertEquals($available, $result->getAvailable());
    }

    public function testWithOffsetStartAtZero()
    {
        $results = new ArrayCollection([
                    self::$DI['record_2']
                ]);

        $query = 'Gotainer';
        $duration = 1 / 3;
        $offsetStart = 0;
        $available = 25;
        $total = 10000;
        $error = 'this is an error message';
        $warning = 'this is a warning message';
        $suggestions = new ArrayCollection([
                        new SearchEngineSuggestion($query, 'Richard', 22)
        ]);
        $propositions = new ArrayCollection();
        $indexes = 'new-index';

        $result = new SearchEngineResult($results, $query, $duration,
                        $offsetStart, $available, $total, $error, $warning,
                        $suggestions, $propositions, $indexes);

        $this->assertEquals(1, $result->getCurrentPage(10));
        $this->assertEquals(1, $result->getCurrentPage(25));
        $this->assertEquals(1, $result->getCurrentPage(40));
    }

}
