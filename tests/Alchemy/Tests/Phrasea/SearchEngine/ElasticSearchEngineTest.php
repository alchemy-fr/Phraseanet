<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

class ElasticSearchEngineTest extends SearchEngineAbstractTest
{
    public function setUp()
    {
        parent::setUp();

        $es = ElasticSearchEngine::create(self::$DI['app']);
        $indexer = new Indexer($es, self::$DI['app']['monolog'], self::$DI['app']['phraseanet.appbox']);
        $indexer->createIndex();
        $indexer->reindexAll();
    }

    public function initialize()
    {
        self::$searchEngine = ElasticSearchEngine::create(self::$DI['app']);
        self::$searchEngineClass = 'Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine';
    }

    public function testAutocomplete()
    {

    }

    protected function updateIndex(array $stemms = [])
    {
        $searchEngine = ElasticSearchEngine::create(self::$DI['app']);
        $searchEngine->getClient()->indices()->refresh(['index' => 'phraseanet']);
    }
}
