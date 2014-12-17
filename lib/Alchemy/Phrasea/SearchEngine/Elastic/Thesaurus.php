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

    const MIN_SCORE = 6;

    public function __construct(Client $client, $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    public function findConceptsBulk(array $terms, $lang = null)
    {
        // TODO Use bulk queries for performance
    }

    public function findConcepts($term, $context = null, $lang = null)
    {
        // TODO Check that term queries are ok with multiple words
        $query = array();
        $field = $lang ? sprintf('value.%s', $lang) : 'value.light';
        $query['match'][$field]['query'] = $term;
        $query['match'][$field]['operator'] = 'and';
        // Allow 25% of non-matching tokens
        // (not exactly the same that 75% of matching tokens)
        // $query['match'][$field]['minimum_should_match'] = '-25%';

        if ($context) {
            $term_query = $query;
            $query = array();
            $query['bool']['must'][0] = $term_query;
            $query['bool']['must'][1]['term']['context'] = $context;
        }

        if ($lang) {
            $term_query = $query;
            $query = array();
            $query['filtered']['query'] = $term_query;
            $query['filtered']['filter']['term']['lang'] = $lang;
        }

        // TODO Only search in a specific databox
        // $term_query = $query;
        // $query = array();
        // $query['filtered']['query'] = $term_query;
        // $query['filtered']['filter']['term']['databox_id'] = $databox_id;

        // Path deduplication
        $aggs = array();
        $aggs['dedup']['terms']['field'] = 'path';

        // Search request
        $params = array();
        $params['index'] = $this->index;
        $params['type'] = TermIndexer::TYPE_NAME;
        $params['body']['query'] = $query;
        $params['body']['aggs'] = $aggs;
        // Arbitrary score low limit, we need find a more granular way to remove
        // inexact concepts.
        // We also need to disable TF/IDF on terms, and try to boost score only
        // when the search match nearly all tokens of term's value field.
        $params['body']['min_score'] = self::MIN_SCORE;

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
