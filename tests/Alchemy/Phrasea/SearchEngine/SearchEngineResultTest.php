<?php

namespace Alchemy\Phrasea\SearchEngine;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class SphinxSearchResultTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineResult
     */
    public function testBasic()
    {
        $results = new ArrayCollection(array(
                    self::$DI['record_24']
                ));

        $query = 'Gotainer';
        $duration = 1 / 3;
        $offsetStart = 24;
        $available = 25;
        $total = 10000;
        $error = 'this is an error message';
        $warning = 'this is a warning message';
        $suggestions = new ArrayCollection(array(
                        new SearchEngineSuggestion($query, 'Richard', 22)
        ));
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

}
