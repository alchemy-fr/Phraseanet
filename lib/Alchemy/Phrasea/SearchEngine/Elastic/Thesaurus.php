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
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Filter;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;
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

    public function findConceptsBulk(array $terms, $lang = null, Filter $filter = null, $strict = false)
    {
        // TODO Use bulk queries for performance
        $concepts = array();
        foreach ($terms as $term) {
            $concepts[] = $this->findConcepts($term, $lang, $filter, $strict);
        }

        return $concepts;
    }

    /**
     * Find concepts linked to the provided Term
     *
     * In strict mode, term context matching is enforced:
     *   `orange (color)` will *not* match `orange` in the index
     *
     * @param  Term|string $term   Term object or a string containing term's value
     * @param  string|null $lang   Input language ("fr", "en", ...) for more effective results
     * @param  Filter|null $filter Filter to restrict search on a specified subset
     * @param  boolean     $strict Whether to enable strict search or not
     * @return Concept[]           Matching concepts
     */
    public function findConcepts($term, $lang = null, Filter $filter = null, $strict = false)
    {
        if (!($term instanceof TermInterface)) {
            $term = new Term($term);
        }

        // TODO Check that term queries are ok with multiple words
        $query = array();
        $field = $lang ? sprintf('value.%s', $lang) : 'value';
        $query['match'][$field]['query'] = $term->getValue();
        $query['match'][$field]['operator'] = 'and';
        // Allow 25% of non-matching tokens
        // (not exactly the same that 75% of matching tokens)
        // $query['match'][$field]['minimum_should_match'] = '-25%';

        if ($term->hasContext()) {
            $term_query = $query;
            $query = array();
            $query['bool']['must'][0] = $term_query;
            $query['bool']['must'][1]['term']['context'] = $term->getContext();
        } elseif ($strict) {
            $context_filter = array();
            $context_filter['missing']['field'] = 'context';
            $query = self::applyQueryFilter($query, $context_filter);
        }

        if ($lang) {
            $lang_filter = array();
            $lang_filter['term']['lang'] = $lang;
            $query = self::applyQueryFilter($query, $lang_filter);
        }

        if ($filter) {
            $query = self::applyQueryFilter($query, $filter->getQueryFilter());
        }

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
        // No need to get any hits since we extract data from aggs
        $params['body']['size'] = 0;

        $response = $this->client->search($params);

        // Extract concept paths from response
        $concepts = array();
        $buckets = \igorw\get_in($response, ['aggregations', 'dedup', 'buckets'], []);
        foreach ($buckets as $bucket) {
            if (isset($bucket['key'])) {
                $concepts[] = new Concept($bucket['key']);
            }
        }

        return $concepts;
    }

    private static function applyQueryFilter(array $query, array $filter)
    {
        if (!isset($query['filtered'])) {
            // Wrap in a filtered query
            $filtered = array();
            $filtered['filtered']['query'] = $query;
            $filtered['filtered']['filter'] = $filter;

            return $filtered;
        } elseif (isset($query['filtered']['filter'])) {
            // Reuse the existing filtered query
            if (!isset($query['filtered']['filter']['bool']['must'])) {
                // Wrap the previous filter in a boolean (must) filter
                $previous_filter = $query['filtered']['filter'];
                $query['filtered']['filter'] = array();
                $query['filtered']['filter']['bool']['must'][0] = $previous_filter;
            }
            $query['filtered']['filter']['bool']['must'][] = $filter;

            return $query;
        } else {
            $query['filtered']['filter'] = $filter;

            return $query;
        }
    }
}
