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

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndexer;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\FacetsResponse;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
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
    /** @var Client */
    private $client;
    private $dateFields;
    private $indexName;
    private $configurationPanel;
    private $locales;
    private $recordHelper;

    public function __construct(Application $app, Client $client, $indexName, array $locales, RecordHelper $recordHelper, Closure $facetsResponseFactory)
    {
        $this->app = $app;
        $this->client = $client;
        $this->locales = array_keys($locales);
        $this->recordHelper = $recordHelper;
        $this->facetsResponseFactory = $facetsResponseFactory;

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
    public function getConfigurationPanel()
    {
        if (!$this->configurationPanel) {
            $this->configurationPanel = new ConfigurationPanel($this->app, $this->app['conf']);
        }

        return $this->configurationPanel;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableDateFields()
    {
        if ($this->dateFields === null) {
            $this->dateFields = $this->recordHelper->getDateFields();
        }

        return $this->dateFields;
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
        return SearchEngineOptions::SORT_RELEVANCE;
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
    public function query($string, $offset, $perPage, SearchEngineOptions $options = null)
    {
        $options = $options ?: new SearchEngineOptions();

        $queryContext = new QueryContext($this->locales, $this->app['locale']);
        $recordQuery = $this->app['query_compiler']->compile($string, $queryContext);

        $params = $this->createRecordQueryParams($recordQuery, $options, null);

        $params['body']['from'] = $offset;
        $params['body']['size'] = $perPage;
        $params['body']['highlight'] = [
            'pre_tags' =>  ['[[em]]'],
            'post_tags' =>  ['[[/em]]'],
            'order' => 'score',
            'fields' => ['caption.*' => new \stdClass()]
        ];

        if ($aggs = $this->getAggregationQueryParams($options)) {
            $params['body']['aggs'] = $aggs;
        }

        $res = $this->client->search($params);

        $results = new ArrayCollection();
        $suggestions = new ArrayCollection();

        $n = 0;
        foreach ($res['hits']['hits'] as $hit) {
            $results[] = ElasticsearchRecordHydrator::hydrate($hit, $n++);
        }

        $facets = $this->facetsResponseFactory->__invoke($res);

        $query['ast'] = $this->app['query_compiler']->parse($string)->dump();
        $query['query_main'] = $recordQuery;
        $query['query'] = $params['body'];
        $query['query_string'] = json_encode($params['body']);

        return new SearchEngineResult($results, json_encode($query), $res['took'], $offset,
            $res['hits']['total'], $res['hits']['total'], null, null, $suggestions, [],
            $this->indexName, $facets);
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
    public function excerpt($query, $fields, \record_adapter $record, SearchEngineOptions $options = null)
    {
        //@todo implements

        return array();
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
        $acl_filters = $this->createACLFilters();

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
        // get business field access rights for current user
        $allowed_databoxes = [];
        $acl = $this->app['acl']->get($this->app['authentication']->getUser());
        foreach ($options->getDataboxes() as $databox) {
            $id = $databox->get_sbas_id();
            if ($acl->can_see_business_fields($databox)) {
                $allowed_databoxes[] = $id;
            }
        }

        $aggs = [];

        // We always a collection facet right now
        $collection_facet_agg = array();
        $collection_facet_agg['terms']['field'] = 'collection_name';
        $aggs['Collection'] = $collection_facet_agg;

        foreach ($this->recordHelper->getFieldsStructure() as $field_name => $params) {
            // skip if field is not searchable or not aggregated
            if (!$params['searchable'] || !$params['to_aggregate']) {
                continue;
            }

            if ($params['private']) {
                // restrict access to authorized databoxes
                $databoxes = array_intersect($params['databox_ids'], $allowed_databoxes);
                $prefix = 'private_caption';
            } else {
                $databoxes = $params['databox_ids'];
                $prefix = 'caption';
            }

            // filter aggregation to allowed databoxes
            // declare aggregation on current field
            $agg = array();
            // TODO (mdarse) Remove databox filtering. It's already done by the
            // ACL filter in the query scope, so no document that shouldn't be
            // displayed can go this far.
            // // array_values is needed to ensure array serialization
            // $agg['filter']['terms']['databox_id'] = array_values($databoxes);
            // $agg['aggs']['distinct_occurrence']['terms']['field'] =
            $agg['terms']['field'] =
                sprintf('%s.%s.raw', $prefix, $field_name);

            $aggs[$field_name] = $agg;
        }

        return $aggs;
    }

    private function createACLFilters()
    {
        // No ACLs if no user
        if (false === $this->app['authentication']->isAuthenticated()) {
            return [];
        }

        $acl = $this->app['acl']->get($this->app['authentication']->getUser());

        $grantedCollections = array_keys($acl->get_granted_base(['actif']));

        if (count($grantedCollections) === 0) {
            return ['bool' => ['must_not' => ['match_all' => new \stdClass()]]];
        }

        $appbox = $this->app['phraseanet.appbox'];

        $flagNamesMap = $this->getFlagsKey($appbox);
        // Get flags rules
        $flagRules = $this->getFlagsRules($appbox, $acl, $grantedCollections);
        // Get intersection between collection ACLs and collection chosen by end user
        $aclRules = $this->getACLsByCollection($flagRules, $flagNamesMap);

        return $this->buildACLsFilters($aclRules);
    }

    private function createQueryFilters(SearchEngineOptions $options)
    {
        $filters = [];

        $filters[]['term']['record_type'] = $options->getSearchType() === SearchEngineOptions::RECORD_RECORD ?
                    SearchEngineInterface::GEM_TYPE_RECORD : SearchEngineInterface::GEM_TYPE_STORY;

        if ($options->getDateFields() && ($options->getMaxDate() || $options->getMinDate())) {
            $range = [];
            if ($options->getMaxDate()) {
                $range['lte'] = $options->getMaxDate()->format(Mapping::DATE_FORMAT_CAPTION_PHP);
            }
            if ($options->getMinDate()) {
                $range['gte'] = $options->getMinDate()->format(Mapping::DATE_FORMAT_CAPTION_PHP);
            }

            foreach ($options->getDateFields() as $dateField) {
                $filters[]['range']['caption.'.$dateField->get_name()] = $range;
            }
        }

        if ($options->getRecordType()) {
            $filters[]['term']['phrasea_type'] = $options->getRecordType();
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
        } else {
            $sort[sprintf('caption.%s', $options->getSortBy())] = $options->getSortOrder();
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
                $flags[$databoxId][$bit] = RecordHelper::normalizeFlagKey($status['labelon']);
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

    private function buildACLsFilters(array $aclRules)
    {
        $filters = [];

        foreach ($aclRules as $baseId => $flagsRules) {
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

            $filters[] = $ruleFilter;
        }

        return ['bool' => ['should' => $filters]];
    }
}
