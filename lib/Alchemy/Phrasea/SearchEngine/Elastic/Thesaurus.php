<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Elasticsearch\Client;

class Thesaurus
{
    private $client;
    private $index;

    public function __construct(Client $client, $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    public function findConcepts($term, $context = null, $lang = null)
    {
        // TODO Check that term queries are ok with multiple words
        $query = array();
        $query['term']['value'] = $term;

        if ($context) {
            $term_query = $query;
            $query = array();
            $query['bool']['must'][0] = $term_query;
            $query['bool']['must'][1]['term']['context'] = $context;
        }

        // Path deduplication
        $aggs = array();
        $aggs['dedup']['terms']['field'] = 'path';

        // Search request
        $params = array();
        $params['type'] = TermIndexer::TYPE_NAME;
        $params['body']['query'] = $query;
        $params['body']['aggs'] = $aggs;
        $response = $this->client->search($params);

        // Extract concept paths from response
        $concepts = array();
        $buckets = \igorw\get_in($response, ['aggregations', 'dedup', 'buckets'], []);
        foreach ($buckets as $bucket) {
            if (isset($bucket['key'])) {
                $concepts[] = $bucket['key'];
            }
        }

        return $concepts;
    }
}
