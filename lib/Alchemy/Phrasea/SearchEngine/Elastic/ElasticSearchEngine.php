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

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\AggregationHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\FacetsResponse;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryCompiler;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContextFactory;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Exception\RuntimeException;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Application;
use Elasticsearch\Client;

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

    public function __construct(Application $app, Structure $structure, Client $client, $indexName, QueryContextFactory $context_factory, Closure $facetsResponseFactory, ElasticsearchOptions $options)
    {
        $this->app = $app;
        $this->structure = $structure;
        $this->client = $client;
        $this->context_factory = $context_factory;
        $this->facetsResponseFactory = $facetsResponseFactory;
        $this->options = $options;

        if ('' === trim($indexName)) {
            throw new \InvalidArgumentException('The provided index name is invalid.');
        }

        $this->indexName = $indexName;

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
        return array_keys($this->structure->getDateFields());
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSort()
    {
        return [
            SearchEngineOptions::SORT_RELEVANCE => $this->app->trans('pertinence'),
            SearchEngineOptions::SORT_CREATED_ON => $this->app->trans('date dajout'),
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

    private function notImplemented()
    {
        throw new LogicException('Not implemented');
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
    public function query($string, SearchEngineOptions $options = null)
    {
        $options = $options ?: new SearchEngineOptions();
        $context = $this->context_factory->createContext($options);

        /** @var QueryCompiler $query_compiler */
        $query_compiler = $this->app['query_compiler'];
        $recordQuery = $query_compiler->compile($string, $context);

        $params = $this->createRecordQueryParams($recordQuery, $options, null);

        // ask ES to return field _version (incremental version number of document)
        $params['body']['version'] = true;

        $params['body']['from'] = $options->getFirstResult();
        $params['body']['size'] = $options->getMaxResults();
        if($this->options->getHighlight()) {
            $params['body']['highlight'] = $this->buildHighlightRules($context);
        }

        $aggs = $this->getAggregationQueryParams($options);

        if ($aggs) {
            $params['body']['aggs'] = $aggs;
        }

        $res = $this->client->search($params);

        $results = new ArrayCollection();

        $n = 0;
        foreach ($res['hits']['hits'] as $hit) {
            $results[] = ElasticsearchRecordHydrator::hydrate($hit, $n++);
        }

        /** @var FacetsResponse $facets */
        $facets = $this->facetsResponseFactory->__invoke($res);

        $query['ast'] = $query_compiler->parse($string)->dump();
        $query['query_main'] = $recordQuery;
        $query['query'] = $params['body'];
        $query['query_string'] = json_encode($params['body']);

        return new SearchEngineResult(
            $options,
            $results,   // ArrayCollection of results
            $string,    // the query as typed by the user
            json_encode($query),
            $res['took'],   // duration
            $options->getFirstResult(),
            $res['hits']['total'],  // available
            $res['hits']['total'],  // total
            null,   // error
            null,   // warning
            $facets->getAsSuggestions(),   // ArrayCollection of suggestions
            [],     // propositions
            $this->indexName,
            $facets
        );
    }

    private function buildHighlightRules(QueryContext $context)
    {
        $highlighted_fields = [];
        foreach ($context->getHighlightedFields() as $field) {
            switch ($field->getType()) {
                case FieldMapping::TYPE_STRING:
                    $index_field = $field->getIndexField();
                    $raw_index_field = $field->getIndexField(true);
                    $highlighted_fields[$index_field] = [
                        // Requires calling Mapping::enableTermVectors() on this field mapping
                        'matched_fields' => [$index_field, $raw_index_field],
                        'type' => 'fvh'
                    ];
                    break;
                case FieldMapping::TYPE_FLOAT:
                case FieldMapping::TYPE_DOUBLE:
                case FieldMapping::TYPE_INTEGER:
                case FieldMapping::TYPE_LONG:
                case FieldMapping::TYPE_SHORT:
                case FieldMapping::TYPE_BYTE:
                    continue;
                case FieldMapping::TYPE_DATE:
                default:
                    continue;
            }
        }

        return [
            'pre_tags' =>  ['[[em]]'],
            'post_tags' =>  ['[[/em]]'],
            'order' => 'score',
            'fields' => $highlighted_fields
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function autocomplete($query, SearchEngineOptions $options)
    {
        throw new RuntimeException('Elasticsearch engine currently does not support auto-complete.');
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

    private function getAggregationQueryParams(SearchEngineOptions $options)
    {
        $aggs = [];

        // We always want a collection facet right now
        $collection_facet_agg = array();
        $collection_facet_agg['terms']['field'] = 'collection_name';
        $aggs['Collection_Name'] = $collection_facet_agg;

        // We always want a base facet right now
        $base_facet_agg = array();
        $base_facet_agg['terms']['field'] = 'databox_name';
        $aggs['Base_Name'] = $base_facet_agg;

        // We always want a type facet right now
        $base_facet_agg = array();
        $base_facet_agg['terms']['field'] = 'type';
        $aggs['Type_Name'] = $base_facet_agg;

        $structure = $this->context_factory->getLimitedStructure($options);
        foreach ($structure->getFacetFields() as $name => $field) {
            // 2015-05-26 (mdarse) Removed databox filtering.
            // It was already done by the ACL filter in the query scope, so no
            // document that shouldn't be displayed can go this far.
            $agg = [];
            $agg['terms']['field'] = $field->getIndexField(true);
            $agg['terms']['size'] = $field->getFacetValuesLimit();
            $aggs[$name] = AggregationHelper::wrapPrivateFieldAggregation($field, $agg);
        }

        return $aggs;
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

    private function createQueryFilters(SearchEngineOptions $options)
    {
        $filters = [];

        $filters[]['term']['record_type'] = $options->getSearchType() === SearchEngineOptions::RECORD_RECORD ?
                    SearchEngineInterface::GEM_TYPE_RECORD : SearchEngineInterface::GEM_TYPE_STORY;

        if ($options->getDateFields() && ($options->getMaxDate() || $options->getMinDate())) {
            $range = [];
            if ($options->getMaxDate()) {
                $range['lte'] = $options->getMaxDate()->format(FieldMapping::DATE_FORMAT_CAPTION_PHP);
            }
            if ($options->getMinDate()) {
                $range['gte'] = $options->getMinDate()->format(FieldMapping::DATE_FORMAT_CAPTION_PHP);
            }

            foreach ($options->getDateFields() as $dateField) {
                $filters[]['range']['caption.'.$dateField->get_name()] = $range;
            }
        }

        if ($type = $options->getRecordType()) {
            $filters[]['term']['type'] = $type;
        }

        $collections = $options->getCollections();
        if (count($collections) > 0) {
            $filters[]['terms']['base_id'] = array_keys($collections);
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

    private function createSortQueryParams(SearchEngineOptions $options)
    {
        $sort = [];

        if ($options->getSortBy() === null || $options->getSortBy() === SearchEngineOptions::SORT_RELEVANCE) {
            $sort['_score'] = $options->getSortOrder();
        } elseif ($options->getSortBy() === SearchEngineOptions::SORT_CREATED_ON) {
            $sort['created_on'] = $options->getSortOrder();
        } elseif ($options->getSortBy() === 'recordid') {
            $sort['record_id'] = $options->getSortOrder();
        } else {
            $sort[sprintf('caption.%s', $options->getSortBy())] = $options->getSortOrder();
        }

        if (! array_key_exists('record_id', $sort)) {
            $sort['record_id'] = $options->getSortOrder();
        }

        return $sort;
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

        $collections = $options->getCollections();

        $collectionsWoRules = [];
        $collectionsWoRules['terms']['base_id'] = [];
        foreach ($aclRules as $baseId => $flagsRules) {
            if(!array_key_exists($baseId, $collections)) {
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
}
