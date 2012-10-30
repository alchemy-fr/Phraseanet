<?php

namespace Alchemy\Phrasea\SearchEngine;

use Doctrine\Common\Collections\ArrayCollection;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class SphinxSearchResultTest extends \PhraseanetPHPUnitAbstract
{

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

        $this->assertEquals($warning, $result->warning());
        $this->assertEquals(2, $result->totalPages(23));
        $this->assertEquals(5, $result->totalPages(5));
        $this->assertEquals($total, $result->total());
        $this->assertEquals($suggestions, $result->suggestions());
        $this->assertEquals($results, $result->results());
        $this->assertEquals($query, $result->query());
        $this->assertEquals($propositions, $result->proposals());
        $this->assertEquals($indexes, $result->indexes());
        $this->assertEquals($error, $result->error());
        $this->assertEquals($duration, $result->duration());
        $this->assertEquals(2, $result->currentPage(23));
        $this->assertEquals($available, $result->available());
    }

}
