<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class SphinxSearchEngine implements SearchEngineInterface
{
    /**
     *
     * @var \SphinxClient
     */
    protected $sphinx;

    /**
     *
     * @var \SphinxClient
     */
    protected $suggestionClient;

    /**
     *
     * @var \PDO
     */
    protected $rt_conn;
    private $dateFields;
    protected $configuration;
    protected $configurationPanel;
    protected $options;
    protected $app;

    public function __construct(Application $app, $host, $port, $rt_host, $rt_port)
    {
        $this->app = $app;
        $this->options = new SearchEngineOptions();

        $this->sphinx = new \SphinxClient();
        $this->sphinx->SetServer($host, $port);
        $this->sphinx->SetArrayResult(true);
        $this->sphinx->SetConnectTimeout(1);

        $this->suggestionClient = new \SphinxClient();
        $this->suggestionClient->SetServer($host, $port);
        $this->suggestionClient->SetArrayResult(true);
        $this->suggestionClient->SetConnectTimeout(1);

        try {
            $this->rt_conn = @new \PDO(sprintf('mysql:host=%s;port=%s;', $rt_host, $rt_port));
            $this->rt_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->rt_conn = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'SphinxSearch';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableDateFields()
    {
        if (!$this->dateFields) {
            foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
                foreach ($databox->get_meta_structure() as $databox_field) {
                    if ($databox_field->get_type() != \databox_field::TYPE_DATE) {
                        continue;
                    }

                    $this->dateFields[] = $databox_field->get_name();
                }
            }

            $this->dateFields = array_unique($this->dateFields);
        }

        return $this->dateFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return 'relevance';
    }

    /**
     * {@inheritdoc}
     */
    public function isStemmingEnabled()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSort()
    {
        return array(
            'relevance'  => _('pertinence'),
            'created_on' => _('date dajout'),
            'random'     => _('aleatoire'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableOrder()
    {
        return array(
            'desc' => _('descendant'),
            'asc'  => _('ascendant'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasStemming()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        if (false === $this->sphinx->Status()) {
            throw new RuntimeException(_('Sphinx server is offline'));
        }

        if (false === $this->suggestionClient->Status()) {
            throw new RuntimeException(_('Sphinx server is offline'));
        }

        if (null === $this->rt_conn) {
            throw new RuntimeException('Unable to connect to sphinx rt');
        }

        return $this->sphinx->Status();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationPanel()
    {
        if (!$this->configurationPanel) {
            $this->configurationPanel = new ConfigurationPanel($this, $this->app['phraseanet.configuration']);
        }

        return $this->configurationPanel;
    }

    public function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = $this->getConfigurationPanel()->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTypes()
    {
        return array(self::GEM_TYPE_RECORD, self::GEM_TYPE_STORY);
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord(\record_adapter $record)
    {
        if (!$this->rt_conn) {
            throw new RuntimeException('Unable to connect to sphinx real-time index');
        }

        $all_datas = array();
        $status = array();

        $binStatus = strrev($record->get_status());

        for ($i = 4; $i < 32; $i++) {
            if ($binStatus[$i]) {
                $status[] = sprintf("%u", crc32($record->get_databox()->get_sbas_id() . '_' . $i));
            }
        }

        $sql_date_fields = $this->getSqlDateFields($record);

        $indexes = array(
            "metas_realtime" . $this->CRCdatabox($record->get_databox()),
            "metas_realtime_stemmed_fr_" . $this->CRCdatabox($record->get_databox()),
            "metas_realtime_stemmed_nl_" . $this->CRCdatabox($record->get_databox()),
            "metas_realtime_stemmed_de_" . $this->CRCdatabox($record->get_databox()),
            "metas_realtime_stemmed_en_" . $this->CRCdatabox($record->get_databox()),
        );

        foreach ($record->get_caption()->get_fields(null, true) as $field) {
            if (!$field->is_indexable()) {
                continue;
            }

            if (!$field->get_databox_field()->isBusiness()) {
                $all_datas[] = $field->get_serialized_values();
            }

            foreach ($field->get_values() as $value) {
                foreach ($indexes as $index) {
                    $this->rt_conn->exec("REPLACE INTO "
                            . $index . " VALUES (
                        '" . $value->getId() . "'
                        ,'" . str_replace("'", "\'", $value->getValue()) . "'
                        ,'" . $value->getDatabox_field()->get_id() . "'
                        ," . $record->get_record_id() . "
                        ," . $record->get_sbas_id() . "
                        ," . $record->get_collection()->get_coll_id() . "
                        ," . (int) $record->is_grouping() . "
                        ," . sprintf("%u", crc32($record->get_sbas_id() . '_' . $value->getDatabox_field()->get_id())) . "
                        ," . sprintf("%u", crc32($record->get_sbas_id() . '_' . $record->get_collection()->get_coll_id())) . "
                        ," . sprintf("%u", crc32($record->get_sbas_id() . '_' . $record->get_record_id())) . "
                        ," . sprintf("%u", crc32($record->get_type())) . "
                        ,0
                        ," . (int) $value->getDatabox_field()->isBusiness() . "
                        ," . sprintf("%u", crc32($record->get_collection()->get_coll_id() . '_' . (int) $value->getDatabox_field()->isBusiness())) . "
                        ," . $record->get_creation_date()->format('U') . "
                        " . $sql_date_fields . "
                        ,(" . implode(',', $status) . ")
                        )");
                }
            }
        }

        $indexes = array(
            "docs_realtime" . $this->CRCdatabox($record->get_databox()),
            "docs_realtime_stemmed_fr_" . $this->CRCdatabox($record->get_databox()),
            "docs_realtime_stemmed_nl_" . $this->CRCdatabox($record->get_databox()),
            "docs_realtime_stemmed_en_" . $this->CRCdatabox($record->get_databox()),
            "docs_realtime_stemmed_de_" . $this->CRCdatabox($record->get_databox()),
        );

        foreach ($indexes as $index) {
            $this->rt_conn->exec("REPLACE INTO "
                    . $index . " VALUES (
                '" . $record->get_record_id() . "'
                ,'" . str_replace("'", "\'", implode(' ', $all_datas)) . "'
                ," . $record->get_record_id() . "
                ," . $record->get_sbas_id() . "
                ," . $record->get_collection()->get_coll_id() . "
                ," . (int) $record->is_grouping() . "
                ," . sprintf("%u", crc32($record->get_sbas_id() . '_' . $record->get_collection()->get_coll_id())) . "
                ," . sprintf("%u", crc32($record->get_sbas_id() . '_' . $record->get_record_id())) . "
                ," . sprintf("%u", crc32($record->get_type())) . "
                ,0
                ," . $record->get_creation_date()->format('U') . "
                " . $sql_date_fields . "
                ,(" . implode(',', $status) . ")
            )");
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRecord(\record_adapter $record)
    {
        if (!$this->rt_conn) {
            throw new RuntimeException('Unable to connect to sphinx real-time index');
        }

        $CRCdatabox = $this->CRCdatabox($record->get_databox());
        $indexes = array(
            "metadatas" . $CRCdatabox,
            "metadatas" . $CRCdatabox . "_stemmed_en",
            "metadatas" . $CRCdatabox . "_stemmed_fr",
            "metadatas" . $CRCdatabox . "_stemmed_de",
            "metadatas" . $CRCdatabox . "_stemmed_nl",
        );
        $RTindexes = array(
            "metas_realtime" . $CRCdatabox,
            "metas_realtime_stemmed_fr_" . $CRCdatabox,
            "metas_realtime_stemmed_en_" . $CRCdatabox,
            "metas_realtime_stemmed_nl_" . $CRCdatabox,
            "metas_realtime_stemmed_de_" . $CRCdatabox,
        );

        foreach ($record->get_caption()->get_fields(null, true) as $field) {

            foreach ($field->get_values() as $value) {

                foreach ($indexes as $index) {
                    $this->sphinx->UpdateAttributes($index, array("deleted"), array($value->getId() => array(1)));
                }

                foreach ($RTindexes as $index) {
                    $this->rt_conn->exec("DELETE FROM " . $index . " WHERE id = " . $value->getId());
                }
            }
        }

        $indexes = array(
            "documents" . $CRCdatabox,
            "documents" . $CRCdatabox . "_stemmed_fr",
            "documents" . $CRCdatabox . "_stemmed_en",
            "documents" . $CRCdatabox . "_stemmed_de",
            "documents" . $CRCdatabox . "_stemmed_nl"
        );
        $RTindexes = array(
            "docs_realtime" . $CRCdatabox,
            "docs_realtime_stemmed_fr_" . $CRCdatabox,
            "docs_realtime_stemmed_en_" . $CRCdatabox,
            "docs_realtime_stemmed_nl_" . $CRCdatabox,
            "docs_realtime_stemmed_de_" . $CRCdatabox,
        );

        foreach ($indexes as $index) {
            $this->sphinx->UpdateAttributes($index, array("deleted"), array($record->get_record_id() => array(1)));
        }

        foreach ($RTindexes as $index) {
            $this->rt_conn->exec("DELETE FROM " . $index . " WHERE id = " . $record->get_record_id());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateRecord(\record_adapter $record)
    {
        $this->removeRecord($record);
        $this->addRecord($record);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addStory(\record_adapter $record)
    {
        return $this->addRecord($record);
    }

    /**
     * {@inheritdoc}
     */
    public function removeStory(\record_adapter $record)
    {
        return $this->removeRecord($record);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStory(\record_adapter $record)
    {
        return $this->updateRecord($record);
    }

    /**
     * {@inheritdoc}
     */
    public function addFeedEntry(\Feed_Entry_Adapter $entry)
    {
        throw new RuntimeException('Feed Entry indexing not supported by Sphinx Search Engine');
    }

    /**
     * {@inheritdoc}
     */
    public function removeFeedEntry(\Feed_Entry_Adapter $entry)
    {
        throw new RuntimeException('Feed Entry indexing not supported by Sphinx Search Engine');
    }

    /**
     * {@inheritdoc}
     */
    public function updateFeedEntry(\Feed_Entry_Adapter $entry)
    {
        throw new RuntimeException('Feed Entry indexing not supported by Sphinx Search Engine');
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(SearchEngineOptions $options)
    {
        $this->options = $options;
        $this->applyOptions($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resetOptions()
    {
        $this->options = new SearchEngineOptions();
        $this->resetSphinx();

        return $this;
    }

    private function resetSphinx()
    {
        $this->sphinx->ResetGroupBy();
        $this->sphinx->ResetFilters();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, $offset, $perPage)
    {
        assert(is_int($offset));
        assert($offset >= 0);
        assert(is_int($perPage));

        $query = $this->parseQuery($query);

        $preg = preg_match('/\s?(recordid|storyid)\s?=\s?([0-9]+)/i', $query, $matches, 0, 0);

        if ($preg > 0) {
            $this->sphinx->SetFilter('record_id', array($matches[2]));
            $query = '';
        }

        $this->sphinx->SetLimits($offset, $perPage);
        $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);

        $index = $this->getQueryIndex($query);
        $res = $this->sphinx->Query($query, $index);

        $results = new ArrayCollection();

        if ($res === false) {
            if ($this->sphinx->IsConnectError() === true) {
                $error = _('Sphinx server is offline');
            } else {
                $error = $this->sphinx->GetLastError();
            }
            $warning = $this->sphinx->GetLastWarning();

            $total = $available = $duration = 0;
            $suggestions = new ArrayCollection();
            $propositions = array();
        } else {
            $error = $res['error'];
            $warning = $res['warning'];

            $duration = $res['time'];
            $total = $res['total_found'];
            $available = $res['total'];

            $resultOffset = $offset;

            if (isset($res['matches'])) {
                foreach ($res['matches'] as $record_id => $match) {
                    try {
                        $record =
                                new \record_adapter(
                                        $this->app,
                                        $match['attrs']['sbas_id'],
                                        $match['attrs']['record_id'],
                                        $resultOffset
                        );

                        $results->add($record);
                    } catch (Exception $e) {

                    }
                    $resultOffset++;
                }
            }

            $suggestions = $this->getSuggestions($query);
            $propositions = '';
        }

        return new SearchEngineResult($results, $query, $duration, $offset, $available, $total, $error, $warning, $suggestions, $propositions, $index);
    }

    /**
     * {@inheritdoc}
     */
    public function autocomplete($query)
    {
        $words = explode(" ", $this->cleanupQuery($query));

        return $this->getSuggestions(array_pop($words));
    }

    /**
     * {@inheritdoc}
     */
    public function excerpt($query, $fields, \record_adapter $record)
    {
        $index = '';
        // in this case search is done on metas
        if ($this->options->getFields() || $this->options->getBusinessFieldsOn()) {
            if ($this->options->isStemmed() && $this->options->getLocale()) {
                $index = 'metadatas' . $this->CRCdatabox($record->get_databox()) . '_stemmed_' . $this->options->getLocale();
            } else {
                $index = 'metadatas' . $this->CRCdatabox($record->get_databox());
            }
        } else {
            if ($this->options->isStemmed() && $this->options->getLocale()) {
                $index = 'documents' . $this->CRCdatabox($record->get_databox()) . '_stemmed_' . $this->options->getLocale();
            } else {
                $index = 'documents' . $this->CRCdatabox($record->get_databox());
            }
        }

        $opts = array(
            'before_match' => "<em>",
            'after_match'  => "</em>",
        );

        $fields_to_send = array();

        foreach ($fields as $k => $f) {
            $fields_to_send[$k] = $f['value'];
        }

        return $this->sphinx->BuildExcerpts($fields_to_send, $index, $query, $opts);
    }

    /**
     * {@inheritdoc}
     */
    public function resetCache()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return SphinxSearchEngine
     */
    public static function create(Application $app, array $options = array())
    {
        $options = ConfigurationPanel::populateConfiguration($options);

        return new static($app, $options['host'], $options['port'], $options['rt_host'], $options['rt_port']);
    }

    /**
     * Return unique integer key for a databox
     *
     * @param  \databox $databox
     * @return int
     */
    public function CRCdatabox(\databox $databox)
    {
        return sprintf("%u", crc32(
                str_replace(
                        array('.', '%')
                        , '_'
                        , sprintf('%s_%s_%s_%s', $databox->get_host(), $databox->get_port(), $databox->get_user(), $databox->get_dbname())
                )
        ));
    }

    /**
     * Reset sphinx client and apply the options
     *
     * Only apply filters and group by
     *
     * @param  SearchEngineOptions $options
     * @return SphinxSearch
     */
    protected function applyOptions(SearchEngineOptions $options)
    {
        $this->resetSphinx();

        $filters = array();

        foreach ($options->getCollections() as $collection) {
            $filters[] = sprintf("%u", crc32($collection->get_databox()->get_sbas_id() . '_' . $collection->get_coll_id()));
        }

        $this->sphinx->SetFilter('crc_sbas_coll', $filters);

        $this->sphinx->SetFilter('deleted', array(0));
        $this->sphinx->SetFilter('parent_record_id', array($options->getSearchType()));

        if ($options->getDateFields() && ($options->getMaxDate() || $options->getMinDate())) {
            foreach (array_unique(array_map(function(\databox_field $field) {
                                return $field->get_name();
                            }, $options->getDateFields())) as $field) {

                $min = $options->getMinDate() ? $options->getMinDate()->format('U') : 0;
                $max = $options->getMaxDate() ? $options->getMaxDate()->format('U') : pow(2, 32);
                $this->sphinx->SetFilterRange(ConfigurationPanel::DATE_FIELD_PREFIX . $field, $min, $max);
            }
        }

        if ($options->getFields()) {

            $filters = array();
            foreach ($options->getFields() as $field) {
                $filters[] = sprintf("%u", crc32($field->get_databox()->get_sbas_id() . '_' . $field->get_id()));
            }

            $this->sphinx->SetFilter('crc_struct_id', $filters);
        }

        if ($options->getBusinessFieldsOn()) {

            $crc_coll_business = array();

            foreach ($options->getBusinessFieldsOn() as $collection) {
                $crc_coll_business[] = sprintf("%u", crc32($collection->get_coll_id() . '_1'));
                $crc_coll_business[] = sprintf("%u", crc32($collection->get_coll_id() . '_0'));
            }

            $non_business = array();

            foreach ($options->getCollections() as $collection) {
                foreach ($options->getBusinessFieldsOn() as $BFcollection) {
                    if ($collection->get_base_id() == $BFcollection->get_base_id()) {
                        continue 2;
                    }
                }
                $non_business[] = $collection;
            }

            foreach ($non_business as $collection) {
                $crc_coll_business[] = sprintf("%u", crc32($collection->get_coll_id() . '_0'));
            }

            $this->sphinx->SetFilter('crc_coll_business', $crc_coll_business);
        } elseif ($options->getFields()) {
            $this->sphinx->SetFilter('business', array(0));
        }

        /**
         * @todo : enhance : check status in a better way
         */
        $status_opts = $options->getStatus();
        foreach ($options->getDataboxes() as $databox) {
            foreach ($databox->get_statusbits() as $n => $status) {
                if (!array_key_exists($n, $status_opts))
                    continue;
                if (!array_key_exists($databox->get_sbas_id(), $status_opts[$n]))
                    continue;
                $crc = sprintf("%u", crc32($databox->get_sbas_id() . '_' . $n));
                $this->sphinx->SetFilter('status', array($crc), ($status_opts[$n][$databox->get_sbas_id()] == '0'));
            }
        }

        if ($options->getRecordType()) {
            $this->sphinx->SetFilter('crc_type', array(sprintf("%u", crc32($options->getRecordType()))));
        }

        $order = '';
        switch ($options->getSortOrder()) {
            case SearchEngineOptions::SORT_MODE_ASC:
                $order = 'ASC';
                break;
            case SearchEngineOptions::SORT_MODE_DESC:
            default:
                $order = 'DESC';
                break;
        }

        switch ($options->getSortBy()) {
            case SearchEngineOptions::SORT_RANDOM:
                $sort = '@random';
                break;
            case SearchEngineOptions::SORT_RELEVANCE:
            default:
                $sort = '@relevance ' . $order . ', created_on ' . $order;
                break;
            case SearchEngineOptions::SORT_CREATED_ON:
                $sort = 'created_on ' . $order;
                break;
        }

        $this->sphinx->SetGroupBy('crc_sbas_record', SPH_GROUPBY_ATTR, $sort);

        return $this;
    }

    private function getSqlDateFields(\record_adapter $record)
    {
        $configuration = $this->getConfiguration();

        $sql_fields = array();

        foreach ($configuration['date_fields'] as $field_name) {

            try {
                $value = $record->get_caption()->get_field($field_name)->get_serialized_values();
            } catch (\Exception $e) {
                $value = null;
            }

            if ($value) {
                $date = \DateTime::createFromFormat('Y/m/d H:i:s', $this->app['unicode']->parseDate($value));
                $value = $date->format('U');
            }

            $sql_fields[] = $value ? : '-1';
        }

        return ($sql_fields ? ', ' : '') . implode(',', $sql_fields);
    }

    /**
     * Remove all keywords, operators, quotes from a query string
     *
     * @param  string $query
     * @return string
     */
    private function cleanupQuery($query)
    {
        return str_replace(array("all", "last", "et", "ou", "sauf", "and", "or", "except", "in", "dans", "'", '"', "(", ")", "_", "-", "+"), ' ', $query);
    }

    /**
     * Return a collection of suggestion corresponding a query
     *
     * @param  string          $query
     * @return ArrayCollection An array collection of SearchEngineSuggestion
     */
    private function getSuggestions($query)
    {
        // First we split the query into simple words
        $words = explode(" ", $this->cleanupQuery(mb_strtolower($query)));

        $tmpWords = array();

        foreach ($words as $word) {
            if (trim($word) === '') {
                continue;
            }
            $tmpWords[] = $word;
        }

        $words = array_unique($tmpWords);

        $altVersions = array();

        foreach ($words as $word) {
            $altVersions[$word] = array($word);
        }

        // As we got words, we look for alternate word for each of them
        if (function_exists('enchant_broker_init') && $this->options->getLocale()) {
            $broker = enchant_broker_init();
            if (enchant_broker_dict_exists($broker, $this->options->getLocale())) {
                $dictionnary = enchant_broker_request_dict($broker, $this->options->getLocale());

                foreach ($words as $word) {

                    if (enchant_dict_check($dictionnary, $word) == false) {
                        $suggs = array_merge(enchant_dict_suggest($dictionnary, $word));
                    }

                    $altVersions[$word] = array_unique($suggs);
                }
                enchant_broker_free_dict($dictionnary);
            }
            enchant_broker_free($broker);
        }

        /**
         * @todo enhance the trigramm query, as it could be sent in one batch
         */
        foreach ($altVersions as $word => $versions) {
            $altVersions[$word] = array_unique(array_merge($versions, $this->get_sugg_trigrams($word)));
        }

        // We now build an array of all possibilities based on the original query
        $queries = array($query);

        foreach ($altVersions as $word => $versions) {
            $tmp_queries = array();
            foreach ($versions as $version) {
                foreach ($queries as $alt_query) {
                    $tmp_queries[] = $alt_query;
                    $tmp_queries[] = str_replace($word, $version, $alt_query);
                }
                $tmp_queries[] = str_replace($word, $version, $query);
            }
            $queries = array_unique(array_merge($queries, $tmp_queries));
        }

        $suggestions = array();
        $max_results = 0;

        foreach ($queries as $alt_query) {
            $results = $this->sphinx->Query($alt_query, $this->getQueryIndex($alt_query));
            if ($results !== false && isset($results['total_found'])) {
                if ($results['total_found'] > 0) {

                    $max_results = max($max_results, (int) $results['total_found']);
                    $suggestions[] = new SearchEngineSuggestion($query, $alt_query, (int) $results['total_found']);
                }
            }
        }

        usort($suggestions, array('self', 'suggestionsHitSorter'));

        $tmpSuggestions = new ArrayCollection();
        foreach ($suggestions as $key => $suggestion) {
            if ($suggestion->getHits() < ($max_results / 100)) {
                continue;
            }
            $tmpSuggestions->add($suggestion);
        }

        return $tmpSuggestions;
    }

    private static function suggestionsHitSorter(SearchEngineSuggestion $a, SearchEngineSuggestion $b)
    {
        if ($a->getHits() == $b->getHits()) {
            return 0;
        }

        return ($a->getHits() > $b->getHits()) ? -1 : 1;
    }

    private function BuildTrigrams($keyword)
    {
        $t = "__" . $keyword . "__";

        $trigrams = "";
        for ($i = 0; $i < strlen($t) - 2; $i++) {
            $trigrams .= substr($t, $i, 3) . " ";
        }

        return $trigrams;
    }

    private function get_sugg_trigrams($word)
    {
        $trigrams = $this->BuildTrigrams($word);
        $query = "\"$trigrams\"/1";
        $len = strlen($word);

        $this->resetSphinx();

        $this->suggestionClient->SetMatchMode(SPH_MATCH_EXTENDED2);
        $this->suggestionClient->SetRankingMode(SPH_RANK_WORDCOUNT);
        $this->suggestionClient->SetFilterRange("len", $len - 2, $len + 4);

        $this->suggestionClient->SetSortMode(SPH_SORT_EXTENDED, "@weight DESC");
        $this->suggestionClient->SetLimits(0, 10);

        $indexes = array();

        foreach ($this->options->getDataboxes() as $databox) {
            $indexes[] = 'suggest' . $this->CRCdatabox($databox);
        }

        $index = implode(',', $indexes);
        $res = $this->suggestionClient->Query($query, $index);

        if ($this->suggestionClient->Status() === false) {
            return array();
        }

        if (!$res || !isset($res["matches"])) {
            return array();
        }

        $words = array();
        foreach ($res["matches"] as $match) {
            $words[] = $match['attrs']['keyword'];
        }

        return $words;
    }

    private function getQueryIndex($query)
    {
        $index = '*';

        $index_keys = array();

        foreach ($this->options->getDataboxes() as $databox) {
            $index_keys[] = $this->CRCdatabox($databox);
        }

        if (count($index_keys) > 0) {
            if ($this->options->getFields() || $this->options->getBusinessFieldsOn()) {
                if ($query !== '' && $this->options->isStemmed() && $this->options->getLocale()) {
                    $index = 'metadatas' . implode('_stemmed_' . $this->options->getLocale() . ', metadatas', $index_keys) . '_stemmed_' . $this->options->getLocale();
                    $index .= ', metas_realtime_stemmed_' . $this->options->getLocale() . '_' . implode(', metas_realtime_stemmed_' . $this->options->getLocale() . '_', $index_keys);
                } else {
                    $index = 'metadatas' . implode(',metadatas', $index_keys);
                    $index .= ', metas_realtime' . implode(', metas_realtime', $index_keys);
                }
            } else {
                if ($query !== '' && $this->options->isStemmed() && $this->options->getLocale()) {
                    $index = 'documents' . implode('_stemmed_' . $this->options->getLocale() . ', documents', $index_keys) . '_stemmed_' . $this->options->getLocale();
                    $index .= ', docs_realtime_stemmed_' . $this->options->getLocale() . '_' . implode(', docs_realtime_stemmed_' . $this->options->getLocale() . '_', $index_keys);
                } else {
                    $index = 'documents' . implode(', documents', $index_keys);
                    $index .= ', docs_realtime' . implode(', docs_realtime', $index_keys);
                }
            }
        }

        return $index;
    }

    private function parseQuery($query)
    {
        $query = trim($query);

        while (substr($query, 0, 1) === '(' && substr($query, -1) === ')') {
            $query = substr($query, 1, (mb_strlen($query) - 2));
        }

        if ($query == 'all') {
            $query = '';
        }

        while (mb_strpos($query, '  ') !== false) {
            $query = str_replace('  ', ' ', $query);
        }

        $offset = 0;
        while (false !== $pos = mb_strpos($query, '-', $offset)) {
            $offset = $pos + 1;
            if ($pos === 0) {
                continue;
            }
            if (mb_substr($query, ($pos - 1), 1) !== ' ') {
                $query = mb_substr($query, 0, ($pos)) . ' ' . mb_substr($query, $pos + 1);
            }
        }

        $query = str_ireplace(array(' ou ', ' or '), '|', $query);
        $query = str_ireplace(array(' sauf ', ' except '), ' -', $query);
        $query = str_ireplace(array(' and ', ' et '), ' +', $query);

        return $query;
    }

    public function buildSuggestions(array $databoxes, $configuration, $threshold = 10)
    {
        $executableFinder = new ExecutableFinder();
        $indexer = $executableFinder->find('indexer');

        if (!is_executable($indexer)) {
            throw new RuntimeException('Indexer does not seem to be executable');
        }

        foreach ($databoxes as $databox) {
            $tmp_file = tempnam(sys_get_temp_dir(), 'sphinx_sugg');

            $cmd = $indexer . ' --config ' . $configuration . ' metadatas' . $this->CRCdatabox($databox)
                    . '  --buildstops ' . $tmp_file . ' 1000000 --buildfreqs';
            $process = new Process($cmd);
            $process->run();

            $sql = 'TRUNCATE suggest';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            if (null !== $sql = $this->BuildDictionarySQL(file_get_contents($tmp_file), $threshold)) {
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }

            unlink($tmp_file);
        }

        return $this;
    }

    protected function BuildDictionarySQL($dictionnary, $threshold)
    {
        $out = array();

        $n = 1;
        $lines = explode("\n", $dictionnary);
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            list ( $keyword, $freq ) = explode(" ", trim($line));

            if ($freq < $threshold || strstr($keyword, "_") !== false || strstr($keyword, "'") !== false) {
                continue;
            }

            if (ctype_digit($keyword)) {
                continue;
            }
            if (mb_strlen($keyword) < 3) {
                continue;
            }

            $trigrams = $this->BuildTrigrams($keyword);

            $out[] = "( $n, '$keyword', '$trigrams', $freq )";
            $n++;
        }

        if ($out) {
            return "INSERT INTO suggest VALUES " . implode(",\n", $out) . ";";
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function clearCache()
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearAllCache(\DateTime $date = null)
    {
        return $this;
    }

}
