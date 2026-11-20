<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SearchEngineSuggestionTest extends \PhraseanetTestCase
{
    public function testSetUp()
    {
        $words = 'plutôt cela';
        $query = 'Batman';
        $hits = 42;

        $suggestion = new SearchEngineSuggestion($query, $words, $hits);
        $this->assertEquals($hits, $suggestion->getHits());
        $this->assertEquals($query, $suggestion->getQuery());
        $this->assertEquals($words, $suggestion->getSuggestion());
    }

    public function testNullHits()
    {
        $words = 'plutôt cela';
        $query = 'Batman';
        $hits = null;

        $suggestion = new SearchEngineSuggestion($query, $words, $hits);
        $this->assertNull($suggestion->getHits());
        $this->assertEquals($query, $suggestion->getQuery());
        $this->assertEquals($words, $suggestion->getSuggestion());
    }

    public function testToArray()
    {
        $words = 'plutôt cela';
        $query = 'Batman';
        $hits = 35;

        $suggestion = new SearchEngineSuggestion($query, $words, $hits);
        $this->assertEquals(['query' => $query, 'hits' => 35, 'suggestion' => $words], $suggestion->toArray());
    }

    public function testToArrayWithNullValue()
    {
        $words = 'plutôt cela';
        $query = 'Batman';
        $hits = null;

        $suggestion = new SearchEngineSuggestion($query, $words, $hits);
        $this->assertEquals(['query' => $query, 'hits' => null, 'suggestion' => $words], $suggestion->toArray());
    }
}
