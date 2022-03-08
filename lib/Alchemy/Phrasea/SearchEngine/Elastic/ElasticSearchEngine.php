<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\AggregationHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\FacetsResponse;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryCompiler;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContextFactory;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field as ESField;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Utilities\Stopwatch;
use Closure;
use databox_field;
use Doctrine\Common\Collections\ArrayCollection;
use Elasticsearch\Client;
use Symfony\Component\Translation\TranslatorInterface;

class ElasticSearchEngine implements SearchEngineInterface
{
    const FLAG_ALLOW_BOTH = 'allow_both';
    const FLAG_SET_ONLY = 'set_only';
    const FLAG_UNSET_ONLY = 'unset_only';

    private $app;
    private $structure;
    /** @var Client */
    private $client;
    private $indexName;

    /** @var ElasticsearchOptions */
    private $options;

    /**
     * @var Closure
     */
    private $facetsResponseFactory;

    /**
     * @var QueryContextFactory
     */
    private $context_factory;

    private $translator;

    /**
     * @param Application $app
     * @param GlobalStructure $structure
     * @param Client $client
     * @param QueryContextFactory $context_factory
     * @param Closure $facetsResponseFactory
     * @param ElasticsearchOptions $options
     * @param TranslatorInterface $translator
     */
    public function __construct(Application $app, GlobalStructure $structure, Client $client, QueryContextFactory $context_factory, Closure $facetsResponseFactory, ElasticsearchOptions $options, TranslatorInterface $translator)
    {
        $this->app = $app;
        $this->structure = $structure;
        $this->client = $client;
        $this->context_factory = $context_factory;
        $this->facetsResponseFactory = $facetsResponseFactory;
        $this->options = $options;
        $this->translator = $translator;

        $this->indexName = $options->getIndexName();
    }

    /**
     * @return Structure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ElasticSearch';
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        $data = $this->client->info();
        $version = $data['version'];
        unset($data['version']);

        foreach ($version as $prop => $value) {
            $data['version:'.$prop] = $value;
        }

        $ret = [];

        foreach ($data as $key => $value) {
            $ret[] = [$key, $value];
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableDateFields()
    {
        // TODO Use limited structure
        return array_keys($this->getStructure()->getDateFields());
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSort()
    {
        return [
            SearchEngineOptions::SORT_RELEVANCE => $this->app->trans('pertinence'),
            SearchEngineOptions::SORT_CREATED_ON => $this->app->trans('date dajout'),
            SearchEngineOptions::SORT_UPDATED_ON => $this->app->trans('date de modification'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return SearchEngineOptions::SORT_CREATED_ON;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSortDirection()
    {
        return SearchEngineOptions::SORT_MODE_DESC;
    }

    /**
     * {@inheritdoc}
     */
    public function isStemmingEnabled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableOrder()
    {
        return [
            SearchEngineOptions::SORT_MODE_DESC => $this->app->trans('descendant'),
            SearchEngineOptions::SORT_MODE_ASC  => $this->app->trans('ascendant'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hasStemming()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTypes()
    {
        return [self::GEM_TYPE_RECORD, self::GEM_TYPE_STORY];
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord(\record_adapter $record)
    {
        $this->notImplemented();
    }

    private function notImplemented()
    {
        throw new LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function removeRecord(\record_adapter $record)
    {
        $this->notImplemented();
    }

    /**
     * {@inheritdoc}
     */
    public function updateRecord(\record_adapter $record)
    {
        $this->notImplemented();
    }

    /**
     * {@inheritdoc}
     */
    public function addStory(\record_adapter $story)
    {
        $this->notImplemented();
    }

    /**
     * {@inheritdoc}
     */
    public function removeStory(\record_adapter $story)
    {
        $this->notImplemented();
    }

    /**
     * {@inheritdoc}
     */
    public function updateStory(\record_adapter $story)
    {
        $this->notImplemented();
    }

    /**
     * {@inheritdoc}
     */
    public function addFeedEntry(FeedEntry $entry)
    {
        throw new RuntimeException('ElasticSearch engine does not support feed entry indexing.');
    }

    /**
     * {@inheritdoc}
     */
    public function removeFeedEntry(FeedEntry $entry)
    {
        throw new RuntimeException('ElasticSearch engine does not support feed entry indexing.');
    }

    /**
     * {@inheritdoc}
     */
    public function updateFeedEntry(FeedEntry $entry)
    {
        throw new RuntimeException('ElasticSearch engine does not support feed entry indexing.');
    }

    /**
     * {@inheritdoc}
     */
    public function query($queryText, SearchEngineOptions $options)
    {
        $context = $this->context_factory->createContext($options);

        /** @var QueryCompiler $query_compiler */
        $query_compiler = $this->app['query_compiler'];
        $queryAST      = $query_compiler->parse($queryText)->dump();
        $queryCompiled = $query_compiler->compile($queryText, $context);

        $queryESLib = $this->createRecordQueryParams($queryCompiled, $options, null);

        // ask ES to return field _version (incremental version number of document)
        $queryESLib['body']['version'] = true;

        $queryESLib['body']['from'] = $options->getFirstResult();
        $queryESLib['body']['size'] = $options->getMaxResults();
        if($this->options->getHighlight()) {
            $queryESLib['body']['highlight'] = $this->buildHighlightRules($context);
        }

        $aggs = $this->getAggregationQueryParams($options);
        if ($aggs) {
            $queryESLib['body']['aggs'] = $aggs;
        }

        $res = $this->client->search($queryESLib);

        $results = new ArrayCollection();
        $n = 0;
        foreach ($res['hits']['hits'] as $hit) {
            $results[] = ElasticsearchRecordHydrator::hydrate($hit, $n++);
        }

        /** @var FacetsResponse $facets */
        $facets = $this->facetsResponseFactory->__invoke($res);

        return new SearchEngineResult(
            $options,
            $results,      // ArrayCollection of results
            $queryText,    // the query as typed by the user
            $queryAST,
            $queryCompiled,
            $queryESLib,
            $res['took'],   // duration
            $options->getFirstResult(),
            count($res['hits']['hits']),  // available
            $res['hits']['total'],  // total
            null,   // error
            null,   // warning
            $facets->getAsSuggestions(),   // ArrayCollection of suggestions
            [],     // propositions
            $this->indexName,
            $facets
        );
    }

    public function queryraw($queryText, SearchEngineOptions $options)
    {
        $stopwatch = new Stopwatch("es");

        $context = $this->context_factory->createContext($options);

        /** @var QueryCompiler $query_compiler */
        $query_compiler = $this->app['query_compiler'];
        $queryAST      = $query_compiler->parse($queryText)->dump();

        $stopwatch->lap("query parse");

        $queryCompiled = $query_compiler->compile($queryText, $context);

        $stopwatch->lap("query compile");

        $queryESLib = $this->createRecordQueryParams($queryCompiled, $options, null);

        $stopwatch->lap("createRecordQueryParams");

        // ask ES to return field _version (incremental version number of document)
        $queryESLib['body']['version'] = true;

        $queryESLib['body']['from'] = $options->getFirstResult();
        $queryESLib['body']['size'] = $options->getMaxResults();
        if($this->options->getHighlight()) {
            $queryESLib['body']['highlight'] = $this->buildHighlightRules($context);
        }

        $stopwatch->lap("buildHighlightRules");

        $aggs = $this->getAggregationQueryParams($options);
        if ($aggs) {
            $queryESLib['body']['aggs'] = $aggs;
        }

        $stopwatch->lap("getAggregationQueryParams");

        $res = $this->client->search($queryESLib);

        $stopwatch->lap("es client search");

        // return $res;

        $results = [];
        foreach ($res['hits']['hits'] as $hit) {
            // remove "path" from subdefs
            foreach($hit['_source']['subdefs'] as $name=>$subdef) {
                unset($hit['_source']['subdefs'][$name]['path']);
            }
            $results[] = $hit;
        }

        $stopwatch->lap("copy hits to results");

        /** @var FacetsResponse $facets */
        $facets = $this->facetsResponseFactory->__invoke($res)->toArray();

        $stopwatch->lap("build facets");

        $stopwatch->stop();

        return [
            '__stopwatch__' => $stopwatch,
            'results' => $results,
            'took' => $res['took'],   // duration
            'count' => count($res['hits']['hits']),  // available
            'total' => $res['hits']['total'],  // total
            'facets' => $facets
        ];
    }

    private function createRecordQueryParams($ESQuery, SearchEngineOptions $options, \record_adapter $record = null)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => RecordIndexer::TYPE_NAME,
            'body'  => [
                'sort'   => $this->createSortQueryParams($options),
            ]
        ];

        $query_filters = $this->createQueryFilters($options);
        $acl_filters = $this->createACLFilters($options);

        $ESQuery = ['filtered' => ['query' => $ESQuery]];

        if (count($query_filters) > 0) {
            $ESQuery['filtered']['filter']['bool']['must'][] = $query_filters;
        }

        if (count($acl_filters) > 0) {
            $ESQuery['filtered']['filter']['bool']['must'][] = $acl_filters;
        }

        $params['body']['query'] = $ESQuery;

        return $params;
    }

    private function createSortQueryParams(SearchEngineOptions $options)
    {
        $sort = [];

        if ($options->getSortBy() === null || $options->getSortBy() === SearchEngineOptions::SORT_RELEVANCE) {
            $sort['_score'] = $options->getSortOrder();
        }
        elseif ($options->getSortBy() === SearchEngineOptions::SORT_CREATED_ON) {
            $sort['created_on'] = $options->getSortOrder();
        }
        elseif ($options->getSortBy() === SearchEngineOptions::SORT_UPDATED_ON) {
            $sort['updated_on'] = $options->getSortOrder();
        }
        elseif ($options->getSortBy() === 'recordid') {
            $sort['record_id'] = $options->getSortOrder();
        }
        else {
            $f = array_filter(
                $options->getFields(),
                function (databox_field $f) use($options) {
                    return $f->get_name() === $options->getSortBy();
                }
            );
            if(count($f) == 1) {
                // the field is found
                $f = array_pop($f);
                /** databox_field $f */
                $k = sprintf('%scaption.%s', $f->isBusiness() ? "private_":"", $options->getSortBy());
                switch ($f->get_type()) {
                    case databox_field::TYPE_DATE:
                        $sort[$k] = [
                            'order' => $options->getSortOrder(),
                            'missing' => "_last",
                            'unmapped_type' => "date"
                        ];
                        break;
                    case databox_field::TYPE_NUMBER:
                        $sort[$k] = [
                            'order' => $options->getSortOrder(),
                            'missing' => "_last",
                            'unmapped_type' => "double"
                        ];
                        break;
                    case databox_field::TYPE_STRING:
                    default:
                        $k .= '.sort';
                        $sort[$k] = [
                            'order' => $options->getSortOrder(),
                            'missing' => "_last",
                            'unmapped_type' => "keyword"
                        ];
                        break;
                }
            }

            /* script tryout
                $sort["_script"] = [
                'type' => "string",
                'script' => [
                    // 'lang' => "painless",
                    'inline' => sprintf(
                        "doc['caption.%s'] ? doc['caption.%s.raw'].value : (doc['private_caption.%s'] ? doc['private_caption.%s.raw'].value : '')",
                        $options->getSortBy(), $options->getSortBy(), $options->getSortBy(), $options->getSortBy()
                    )
                ],
                'order' => "asc"
            ];
            */
        }

        if (!array_key_exists('record_id', $sort)) {
            $sort['record_id'] = $options->getSortOrder();
        }

        return $sort;
    }

    private function createQueryFilters(SearchEngineOptions $options)
    {
        $filters = [];

        $filters[]['term']['record_type'] = $options->getSearchType() === SearchEngineOptions::RECORD_RECORD ?
                    SearchEngineInterface::GEM_TYPE_RECORD : SearchEngineInterface::GEM_TYPE_STORY;

        if ($options->getDateFields() && ($options->getMaxDate() || $options->getMinDate())) {
            $range = [];
            if ($options->getMaxDate()) {
                $range['lte'] = $options->getMaxDate()->format('Y-m-d');
            }
            if ($options->getMinDate()) {
                $range['gte'] = $options->getMinDate()->format('Y-m-d');
            }

            foreach ($options->getDateFields() as $dateField) {
                $filters[]['range']['caption.'.$dateField->get_name()] = $range;
            }
        }

        if ($type = $options->getRecordType()) {
            $filters[]['term']['type'] = $type;
        }

        $bases = $options->getBasesIds();
        if (count($bases) > 0) {
            $filters[]['terms']['base_id'] = $bases;
        }

        if (count($options->getStatus()) > 0) {
            $status_filters = [];
            $flagNamesMap = $this->getFlagsKey($this->app['phraseanet.appbox']);

            foreach ($options->getStatus() as $databoxId => $status) {
                $status_filter = $databox_status =[];
                $status_filter[] = ['term' => ['databox_id' => $databoxId]];
                foreach ($status as $n => $v) {
                    if (!isset($flagNamesMap[$databoxId][$n])) {
                        continue;
                    }

                    $label = $flagNamesMap[$databoxId][$n];
                    $databox_status[] = ['term' => [sprintf('flags.%s', $label) => (bool) $v]];
                };
                $status_filter[] = $databox_status;

                $status_filters[] = ['bool' => ['must' => $status_filter]];
            }
            $filters[]['bool']['should'] = $status_filters;
        }

        return $filters;
    }

    private function getFlagsKey(\appbox $appbox)
    {
        $flags = [];
        foreach ($appbox->get_databoxes() as $databox) {
            $databoxId = $databox->get_sbas_id();
            $statusStructure = $databox->getStatusStructure();
            foreach($statusStructure as $bit => $status) {
                $flags[$databoxId][$bit] = Flag::normalizeName($status['labelon']);
            }
        }

        return $flags;
    }

    private function createACLFilters(SearchEngineOptions $options)
    {
        // No ACLs if no user
        if (false === $this->app->getAuthenticator()->isAuthenticated()) {
            return [];
        }

        $acl = $this->app->getAclForUser($this->app->getAuthenticatedUser());

        $grantedCollections = array_keys($acl->get_granted_base([\ACL::ACTIF]));

        if (count($grantedCollections) === 0) {
            return ['bool' => ['must_not' => ['match_all' => new \stdClass()]]];
        }

        $appbox = $this->app['phraseanet.appbox'];

        $flagNamesMap = $this->getFlagsKey($appbox);
        // Get flags rules
        $flagRules = $this->getFlagsRules($appbox, $acl, $grantedCollections);
        // Get intersection between collection ACLs and collection chosen by end user
        $aclRules = $this->getACLsByCollection($flagRules, $flagNamesMap);

        return $this->buildACLsFilters($aclRules, $options);
    }

    private function getFlagsRules(\appbox $appbox, \ACL $acl, array $collections)
    {
        $rules = [];
        foreach ($collections as $collectionId) {
            $databoxId = \phrasea::sbasFromBas($this->app, $collectionId);
            $databox = $appbox->get_databox($databoxId);

            $mask_xor = $acl->get_mask_xor($collectionId);
            $mask_and = $acl->get_mask_and($collectionId);
            foreach ($databox->getStatusStructure()->getBits() as $bit) {
                $rules[$databoxId][$collectionId][$bit] = $this->computeAccess(
                    $mask_xor,
                    $mask_and,
                    $bit
                );
            }
        }

        return $rules;
    }

    /**
     *    Truth table for status rights
     *
     *    +-----------+
     *    | and | xor |
     *    +-----------+
     *    |  0  |  0  | -> BOTH STATES ARE CHECKED
     *    +-----------+
     *    |  1  |  0  | -> UNSET STATE IS CHECKED
     *    +-----------+
     *    |  0  |  1  | -> UNSET STATE IS CHECKED (not possible)
     *    +-----------+
     *    |  1  |  1  | -> SET STATE IS CHECKED
     *    +-----------+
     *
     */
    private function computeAccess($and, $xor, $bit)
    {
        $xorBit = \databox_status::bitIsSet($xor, $bit);
        $andBit = \databox_status::bitIsSet($and, $bit);

        if (!$xorBit && !$andBit) {
            return self::FLAG_ALLOW_BOTH;
        }

        if ($xorBit && $andBit) {
            return self::FLAG_SET_ONLY;
        }

        return self::FLAG_UNSET_ONLY;
    }

    private function getACLsByCollection(array $flagACLs, array $flagNamesMap)
    {
        $rules = [];

        foreach ($flagACLs as $databoxId => $bases) {
            foreach ($bases as $baseId => $bit) {
                $rules[$baseId] = [];
                foreach ($bit as $n => $rule) {
                    if (!isset($flagNamesMap[$databoxId][$n])) {
                        continue;
                    }

                    $label = $flagNamesMap[$databoxId][$n];
                    $rules[$baseId][$label] = $rule;
                }
            }
        }

        return $rules;
    }

    private function buildACLsFilters(array $aclRules, SearchEngineOptions $options)
    {
        $filters = [];

        $bases = $options->getBasesIds();

        $collectionsWoRules = [];
        $collectionsWoRules['terms']['base_id'] = [];
        foreach ($aclRules as $baseId => $flagsRules) {
            if(!in_array($baseId, $bases)) {
                // no need to add a filter if the collection is not searched
                continue;
            }

            $ruleFilter = $baseFilter = [];

            // filter on base
            $baseFilter['term']['base_id'] = $baseId;
            $ruleFilter['bool']['must'][] = $baseFilter;

            // filter by flags
            foreach ($flagsRules as $flagName => $flagRule) {
                // only add filter if one of the status state is not allowed / allowed
                if ($flagRule === self::FLAG_ALLOW_BOTH) {
                    continue;
                }
                $flagFilter = [];

                $flagField = sprintf('flags.%s', $flagName);
                $flagFilter['term'][$flagField] = $flagRule === self::FLAG_SET_ONLY ? true : false;

                $ruleFilter['bool']['must'][] = $flagFilter;
            }

            if(count($ruleFilter['bool']['must']) > 1) {
                // some rules found, add the filter
                $filters[] = $ruleFilter;
            }
            else {
                // no rules for this collection, add it to the 'simple' list
                $collectionsWoRules['terms']['base_id'][] = $baseId;
            }
        }
        if (count($collectionsWoRules['terms']['base_id']) > 0) {
            // collections w/o rules : add a simple list ?
            if(count($filters) > 0) {   // no need to add a big 'should' filter only on collections
                $filters[] = $collectionsWoRules;
            }
        }

        if(count($filters) > 0) {
            return ['bool' => ['should' => $filters]];
        }
        else {
            return [];
        }
    }

    private function buildHighlightRules(QueryContext $context)
    {
        $highlighted_fields = [];
        foreach ($context->getHighlightedFields() as $field) {
            switch ($field->getType()) {
                case FieldMapping::TYPE_STRING:
                case FieldMapping::TYPE_DOUBLE:
                case FieldMapping::TYPE_DATE:
                    $index_field = $field->getIndexField();
                    $raw_index_field = $field->getIndexField(true);
                    $highlighted_fields[$index_field . ".light"] = [
                        // Requires calling Mapping::enableTermVectors() on this field mapping
//                        'matched_fields' => [$index_field, $raw_index_field],
                        'type' => 'fvh',
                    ];
                    break;
                case FieldMapping::TYPE_FLOAT:
                case FieldMapping::TYPE_INTEGER:
                case FieldMapping::TYPE_LONG:
                case FieldMapping::TYPE_SHORT:
                case FieldMapping::TYPE_BYTE:
                default:
                    continue;
            }
        }

        return [
            'pre_tags'  => ['[[em]]'],
            'post_tags' => ['[[/em]]'],
            'order'     => 'score',
            'fields'    => $highlighted_fields
        ];
    }

    private function getAggregationQueryParams(SearchEngineOptions $options)
    {
        $aggs = [];
        // technical aggregates (enable + optional limit)
        foreach (ElasticsearchOptions::getAggregableTechnicalFields($this->translator) as $k => $f) {
            $size = $this->options->getAggregableFieldLimit($k);
            if ($size !== databox_field::FACET_DISABLED) {
                if ($size === databox_field::FACET_NO_LIMIT) {
                    $size = ESField::FACET_NO_LIMIT;
                }
                $agg = [
                    'terms' => [
                        'field' => $f['esfield'],
                        'size'  => $size
                    ]
                ];
                $aggs[$k] = $agg;
                if($options->getIncludeUnsetFieldFacet() === true) {
                    $aggs[$k . '#empty'] = [
                        'missing' => [
                            'field' => $f['esfield'],
                        ]
                    ];
                }
            }
        }
        // fields aggregates
        $structure = $this->context_factory->getLimitedStructure($options);
        foreach($structure->getAllFields() as $name => $field) {
            $size = $this->options->getAggregableFieldLimit($name);
            if ($size !== databox_field::FACET_DISABLED) {
                if ($size === databox_field::FACET_NO_LIMIT) {
                    $size = ESField::FACET_NO_LIMIT;
                }
                $agg = [
                    'terms' => [
                        'field' => $field->getIndexField(true),
                        'size'  => $size
                    ]
                ];
                $aggs[$name] = AggregationHelper::wrapPrivateFieldAggregation($field, $agg);

                if($options->getIncludeUnsetFieldFacet() === true) {
                    $aggs[$name . '#empty'] = AggregationHelper::wrapPrivateFieldAggregation(
                        $field,
                        [
                            'missing' => [
                                'field' => $field->getIndexField(true),
                            ]
                        ]
                    );
                }
            }
        }

        return $aggs;
    }

    /**
     * {@inheritdoc}
     */
    public function autocomplete($query, SearchEngineOptions $options)
    {
        $params = $this->createCompletionParams($query, $options);

        $res = $this->client->suggest($params);

        $ret = [
            'text'    => [],
            'byField' => []
        ];
        foreach (array_keys($params['body']) as $fname) {
            $t = [];
            foreach ($res[$fname] as $suggest) {    // don't know why there is a sub-array level
                foreach ($suggest['options'] as $option) {
                    $text = $option['text'];
                    if (!in_array($text, $ret['text'])) {
                        $ret['text'][] = $text;
                    }
                    $t[] = [
                        'label' => $text,
                        'query' => $fname . ':' . $text
                    ];
                }
            }
            if (!empty($t)) {
                $ret['byField'][$fname] = $t;
            }
        }

        return $ret;
    }

    private function createCompletionParams($query, SearchEngineOptions $options)
    {
        $body = [];
        $context = [
            'record_type' => $options->getSearchType() === SearchEngineOptions::RECORD_RECORD ?
                SearchEngineInterface::GEM_TYPE_RECORD : SearchEngineInterface::GEM_TYPE_STORY
        ];

        $base_ids = $options->getBasesIds();
        if (count($base_ids) > 0) {
            $context['base_id'] = $base_ids;
        }

        $search_context = $this->context_factory->createContext($options);
        $fields = $search_context->getUnrestrictedFields();
        foreach ($fields as $field) {
            if ($field->getType() == FieldMapping::TYPE_STRING) {
                $k = '' . $field->getName();
                $body[$k] = [
                    'text'       => $query,
                    'completion' => [
                        'field'   => "caption." . $field->getName() . ".suggest",
                        'context' => &$context
                    ]
                ];
            }
        }

        return [
            'index' => $this->indexName,
            'body'  => $body
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resetCache()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllCache(\DateTime $date = null)
    {
    }
}
