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
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class Thesaurus
{
    /** @var Client */
    private $client;
    /** @var ElasticsearchOptions */
    private $options;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Client $client, ElasticsearchOptions $options, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->options = $options;
        $this->logger = $logger;
    }

    /**
     * Find concepts linked to a bulk of Terms
     *
     * @param  Term[]|string[]      $terms  Term objects or strings
     * @param  string|null          $lang   Input language
     * @param  Filter[]|Filter|null $filter Single filter or a filter for each term
     * @param  boolean              $strict Strict mode matching
     * @return Concept[][]                  List of matching concepts for each term
     */
    public function findConceptsBulk(array $terms, $lang = null, $filter = null, $strict = false)
    {
        $this->logger->debug(sprintf('Finding linked concepts in bulk for %d terms', count($terms)));

        // We use the same filter for all terms when a single one is given
        $filters = is_array($filter)
            ? $filter
            : array_fill_keys(array_keys($terms), $filter);
        if (array_diff_key($terms, $filters)) {
            throw new InvalidArgumentException('Filters list must contain a filter for each term');
        }

        // TODO Use bulk queries for performance
        $concepts = array();
        foreach ($terms as $index => $term) {
            $strict |= ($term instanceof AST\TermNode);      // a "term" node is [strict group of words]
            $concepts[] = $this->findConcepts($term, $lang, $filters[$index], $strict);
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
    private function findConcepts($term, $lang = null, Filter $filter = null, $strict = false)
    {
        return $strict ?
            $this->findConceptsStrict($term, $lang, $filter)
            :
            $this->findConceptsFuzzy($term, $lang, $filter)
            ;
    }

    private function findConceptsStrict($term, $lang = null, Filter $filter = null)
    {
        if (!($term instanceof TermInterface)) {
            $term = new Term($term);
        }

        $this->logger->info(sprintf('Searching for term %s', $term), array(
            'strict' => true,
            'lang' => $lang
        ));

        $must = [];
        $filters = [];

        $must[] = [
            'match' => [
                'value.strict' => [
                    'query' => $term->getValue(),
                    'operator' => 'and',
                ],
            ],
        ];
        if ($term->hasContext()) {
            $must[] = [
                'match' => [
                    'context.strict' => [
                        'query' => $term->getContext(),
                        'operator' => 'and',
                    ],
                ],
            ];
        } else {
            $filters[] = [
                'missing' => [
                    'field' => 'context'
                ]
            ];
        }
/*
        if(count($databoxIds) > 0) {
            if(count($databoxIds) == 1) {
                $filters[] = [
                    'term' => [
                        'databox_id' => $databoxIds[0]
                    ]
                ];
            }
            else {
                $filters[] = [
                    'terms' => [
                        'databox_id' => $databoxIds
                    ]
                ];
            }
        }
*/
        if ($lang) {
            $filters[] = [
                'term' => [
                    'lang' => $lang
                ]
            ];
        }

        if ($filter) {
            $filters = array_merge($filters, $filter->getQueryFilters());
        }
        if(!empty($filters)) {
            if (count($filters) > 1) {
                $must[] = [
                    'constant_score' => [
                        'filter' => [
                            'and' => $filters
                        ]
                    ]
                ];
            }
            else {
                $must[] = [
                    'constant_score' => [
                        'filter' => $filters[0]
                    ]
                ];
            }
        }

        if(count($must) > 1) {
            $query = [
                'bool' => [
                    'must' => $must
                ]
            ];
        }
        else {
            $query = $must[0];
        }

        // Search request
        $params = [
            'index' => $this->options->getIndexName(),
            'type'  => TermIndexer::TYPE_NAME,
            'body'  => [
                'query' => $query,
                'aggs'  => [
                    // Path deduplication
                    'db' => [                           // databox_id
                        'terms' => [
                            'field' => 'databox_id'
                        ],
                        'aggs'  => [
                            // Path deduplication
                            'cp' => [                   // concept_path
                                'terms' => [
                                    'field' => 'path.raw'
                                ]
                            ]
                        ],

                    ]
                ],
                // No need to get any hits since we extract data from aggs
                'size' => 0
            ]
        ];


        $this->logger->debug('Sending search', $params['body']);
        $response = $this->client->search($params);

        // Extract concept paths from response
        $concepts = array();
        $db_buckets = \igorw\get_in($response, ['aggregations', 'db', 'buckets'], []);
        $keys = array();
        foreach ($db_buckets as $db_bucket) {
            if (isset($db_bucket['key'])) {
                $db = $db_bucket['key'];
                $cp_buckets = \igorw\get_in($db_bucket, ['cp', 'buckets'], []);
                foreach ($cp_buckets as $cp_bucket) {
                    if (isset($cp_bucket['key'])) {
                        $keys[] = $cp_bucket['key'];
                        $concepts[] = new Concept($db, $cp_bucket['key']);
                    }
                }
            }
        }

        $this->logger->info(sprintf('Found %d matching concepts', count($concepts)),
            array('concepts' => $keys)
        );

        return $concepts;
    }

    private function findConceptsFuzzy($term, $lang = null, Filter $filter = null)
    {
        if (!($term instanceof TermInterface)) {
            $term = new Term($term);
        }

        $this->logger->info(sprintf('Searching for term %s', $term), array(
            'strict' => false,
            'lang' => $lang
        ));

        if($lang) {
            $field_suffix = sprintf('.%s', $lang);
        } else {
            $field_suffix = '';
        }

        $field = sprintf('value%s', $field_suffix);
        $query = array();
        $query['match'][$field]['query'] = $term->getValue();
        $query['match'][$field]['operator'] = 'and';
        // Allow 25% of non-matching tokens
        // (not exactly the same that 75% of matching tokens)
        // $query['match'][$field]['minimum_should_match'] = '-25%';

        if ($term->hasContext()) {
            $value_query = $query;
            $field = sprintf('context%s', $field_suffix);
            $context_query = array();
            $context_query['match'][$field]['query'] = $term->getContext();
            $context_query['match'][$field]['operator'] = 'and';
            $query = array();
            $query['bool']['must'][0] = $value_query;
            $query['bool']['must'][1] = $context_query;
        }
/*
        if(count($databoxIds) > 0) {
            if(count($databoxIds) == 1) {
                $query = self::applyQueryFilter(
                    $query,
                    [
                        'term' => [
                            'databox_id' => $databoxIds[0]
                        ]
                    ]
                );
            }
            else {
                $query = self::applyQueryFilter(
                    $query,
                    [
                        'terms' => [
                            'databox_id' => $databoxIds
                        ]
                    ]
                );
            }
        }
*/
        if ($lang) {
            $lang_filter = array();
            $lang_filter['term']['lang'] = $lang;
            $query = self::applyQueryFilter($query, $lang_filter);
        }

        if ($filter) {
            $this->logger->debug('Using filter', array('filter' => Filter::dump($filter)));
            $query = self::applyQueryFilter($query, $filter->getQueryFilter());
        }
        $params = [
            'index' => $this->options->getIndexName(),
            'type'  => TermIndexer::TYPE_NAME,
            'body'  => [
                'query' => $query,
                'aggs'  => [
                    // Path deduplication
                    'db' => [                           // databox_id
                        'terms' => [
                            'field' => 'databox_id'
                        ],
                        'aggs'  => [
                            // Path deduplication
                            'cp' => [                   // concept_path
                                'terms' => [
                                    'field' => 'path.raw'
                                ]
                            ]
                        ],

                    ]
                ],
                // Arbitrary score low limit, we need find a more granular way to remove
                // inexact concepts.
                // We also need to disable TF/IDF on terms, and try to boost score only
                // when the search match nearly all tokens of term's value field.
                'min_score' => $this->options->getMinScore(),
                // No need to get any hits since we extract data from aggs
                'size' => 0
            ]
        ];

        $this->logger->debug('Sending search', $params['body']);
        $response = $this->client->search($params);

        // Extract concept paths from response
        $concepts = [];
        $db_buckets = \igorw\get_in($response, ['aggregations', 'db', 'buckets'], []);
        $keys = array();
        foreach ($db_buckets as $db_bucket) {
            if (isset($db_bucket['key'])) {
                $db = $db_bucket['key'];
                $cp_buckets = \igorw\get_in($db_bucket, ['cp', 'buckets'], []);
                foreach ($cp_buckets as $cp_bucket) {
                    if (isset($cp_bucket['key'])) {
                        $keys[] = $cp_bucket['key'];
                        $concepts[] = new Concept($db, $cp_bucket['key']);
                    }
                }
            }
        }

        $this->logger->info(sprintf('Found %d matching concepts', count($concepts)),
            array('concepts' => $keys)
        );

        return $concepts;
    }

    private static function applyQueryFilter(array $query, array $filters)
    {
        if (!isset($query['filtered'])) {
            // Wrap in a filtered query
            $query = ['filtered' => ['query' => $query, 'filter' => []]];
        }
        elseif (!isset($query['filtered']['filter'])) {
            $query['filtered']['filter'] = [];
        }

        self::addFilters($query['filtered']['filter'], $filters);

        return $query;
    }

    /**
     * @param array $current_filters BY REF !
     * @param array $new_filters
     *
     * add filters to existing filters, wrapping with bool/must if necessary
     */
    private static function addFilters(array &$current_filters, array $new_filters)
    {
        foreach($new_filters as $verb=>$new_filter) {
            foreach ($new_filter as $f=>$v) {
                if(count($current_filters) == 0) {
                    $current_filters = [$verb => [$f=>$v]];
                }
                else {
                    if (!isset($current_filters['bool']['must'])) {
                        // Wrap the previous filter in a boolean (must) filter
                        $current_filters = ['bool' => ['must' => [$current_filters]]];
                    }
                    $current_filters['bool']['must'][] = [$verb => [$f => $v]];
                }
            }
        }
    }
}
