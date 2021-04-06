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
    public function findConcepts($term, $lang = null, Filter $filter = null, $strict = false)
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
        $mustnot = [];
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
        }
        else {
            $mustnot[] = [
                'exists' => [
                    'field' => 'context'
                ]
            ];
        }
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

        $query = [
            'bool' => [
                'must'     => $must,
                'must_not' => $mustnot,
                'filter'   => $filters,
            ]
        ];
        /*
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
*/
        // Search request
        $params = [
            'index' => $this->options->getIndexName() . '.t',
            'type'  => TermIndexer::TYPE_NAME,
            'body'  => [
                'query' => $query,
                'aggs'  => [
                    // Path deduplication
                    'dedup' => [
                        'terms' => [
                            'field' => 'path.raw'
                        ]
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
        $buckets = \igorw\get_in($response, ['aggregations', 'dedup', 'buckets'], []);
        $keys = array();
        foreach ($buckets as $bucket) {
            if (isset($bucket['key'])) {
                $keys[] = $bucket['key'];
                $concepts[] = new Concept($bucket['key']);
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
        $query = [
            'match' => [
                $field => [
                    'query' => $term->getValue(),
                    'operator' => 'and'
                ]
            ]
        ];
        // Allow 25% of non-matching tokens
        // (not exactly the same that 75% of matching tokens)
        // $query['match'][$field]['minimum_should_match'] = '-25%';

        if ($term->hasContext()) {
            $query = [
                'bool' => [
                    'must' => [
                        $query,
                        [
                            'match' => [
                                $field => [
                                    'query' => $term->getContext(),
                                    'operator' => 'and'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        if ($lang) {
            $lang_filter = array();
            $lang_filter['term']['lang'] = $lang;
            $query = self::applyQueryFilter($query, $lang_filter);
        }

        if ($filter) {
            $this->logger->debug('Using filter', array('filter' => Filter::dump($filter)));
            $query = self::applyQueryFilter($query, $filter->getQueryFilter());
        }

        // Search request
        $params = [
            'index' => $this->options->getIndexName() . '.t',
            // 'type'  => TermIndexer::TYPE_NAME,
            'body'  => [
                'query' => $query,
                'aggs'  => [
                    // Path deduplication
                    'dedup' => [
                        'terms' => [
                            'field' => 'path.raw'
                        ]
                    ]
                ],
                // Arbitrary score low limit, we need find a more granular way to remove
                // inexact concepts.
                // We also need to disable TF/IDF on terms, and try to boost score only
                // when the search match nearly all tokens of term's value field.
                'min_score' => $this->options->getMinScore(),
                // No need to get any hits since we extract data from aggs
                'size'      => 0
            ]
        ];

        $this->logger->debug('Sending search', $params['body']);
        $response = $this->client->search($params);

        // Extract concept paths from response
        $concepts = array();
        $buckets = \igorw\get_in($response, ['aggregations', 'dedup', 'buckets'], []);
        $keys = array();
        foreach ($buckets as $bucket) {
            if (isset($bucket['key'])) {
                $keys[] = $bucket['key'];
                $concepts[] = new Concept($bucket['key']);
            }
        }

        $this->logger->info(sprintf('Found %d matching concepts', count($concepts)),
            array('concepts' => $keys)
        );

        return $concepts;
    }

    private static function applyQueryFilter(array $query, array $filters)
    {
        if (!isset($query['bool'])) {
            // Wrap in a filtered query
            $query = ['bool' => ['must' => $query, 'filter' => []]];
        }
        elseif (!isset($query['bool']['filter'])) {
            $query['bool']['filter'] = [];
        }

        self::addFilters($query['bool']['filter'], $filters);

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
