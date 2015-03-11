<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

class ElasticSearchEngineTest extends SearchEngineAbstractTest
{
    public function setUp()
    {
        $this->markTestSkipped();
        if (false === @file_get_contents('http://localhost:9200')) {
            $this->markTestSkipped('Unable to connect to elasticsearch.');
        }

        parent::setUp();

        /** @var Indexer $indexer */
        $indexer = self::$DI['app']['elasticsearch.indexer'];

        // Re-index everything
        ob_start();
        $indexer->deleteIndex();
        $indexer->createIndex();
        $indexer->populateIndex();
        ob_end_clean();
    }

    public function initialize()
    {
        // Change the index name
        self::$DI['app']['conf']->set(['main', 'search-engine', 'options', 'index'], 'test');

        self::$searchEngine = $es = new ElasticSearchEngine(
            self::$DI['app'],
            self::$DI['app']['elasticsearch.client'],
            self::$DI['app']['elasticsearch.options']['index']
        );

        self::$searchEngineClass = 'Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine';
    }

    public function testAutocomplete()
    {
        $this->markTestSkipped("Not implemented yet.");
    }

    protected function updateIndex(array $stemms = [])
    {
        $client = self::$searchEngine->getClient();
        $client->indices()->refresh();
    }
}
