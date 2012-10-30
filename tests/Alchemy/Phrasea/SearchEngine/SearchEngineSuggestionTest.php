<?php

namespace Alchemy\Phrasea\SearchEngine;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class SearchEngineSuggestionTest extends \PhraseanetPHPUnitAbstract
{
    public function testSetUp()
    {
        $words = 'plutôt cela';
        $query = 'Katy Query';
        $hits = 42;
        
        $suggestion = new SearchEngineSuggestion($query, $words, $hits);
        $this->assertEquals($hits, $suggestion->hits());
        $this->assertEquals($query, $suggestion->query());
        $this->assertEquals($words, $suggestion->suggestion());
    }
}
