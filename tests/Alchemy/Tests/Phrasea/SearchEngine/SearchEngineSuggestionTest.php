<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;

class SearchEngineSuggestionTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion
     */
    public function testSetUp()
    {
        $words = 'plutôt cela';
        $query = 'Katy Query';
        $hits = 42;

        $suggestion = new SearchEngineSuggestion($query, $words, $hits);
        $this->assertEquals($hits, $suggestion->getHits());
        $this->assertEquals($query, $suggestion->getQuery());
        $this->assertEquals($words, $suggestion->getSuggestion());
    }
}
