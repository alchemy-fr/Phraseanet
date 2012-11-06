<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;

class PhraseaEngine implements SearchEngineInterface
{
    private $initialized;

    /**
     *
     * @var SearchEngineOptions
     */
    private $options;
    private $app;
    private $dateFields;
    private $configuration;
    private $queries = array();
    private $arrayq = array();
    private $colls = array();
    private $qp = array();
    private $needthesaurus = array();
    private $configurationPanel;
    private $resetCacheNextQuery = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->options = new SearchEngineOptions();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Phrasea';
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
    public function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = $this->configurationPanel()->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        $configuration = $this->getConfiguration();
        
        return $configuration['default_sort'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSort()
    {
        $date_fields = $this->getAvailableDateFields();

        $sort = array('' => _('No sort'));

        foreach ($date_fields as $field) {
            $sort[$field] = $field;
        }
        
        return $sort;
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
        return false;
    }

    public function initialize()
    {
        if ($this->initialized) {
            return $this;
        }

        $choosenConnexion = $this->app['phraseanet.configuration']->getPhraseanet()->get('database');

        $connexion = $this->app['phraseanet.configuration']->getConnexion($choosenConnexion);

        $hostname = $connexion->get('host');
        $port = (int) $connexion->get('port');
        $user = $connexion->get('user');
        $password = $connexion->get('password');
        $dbname = $connexion->get('dbname');

        if (!extension_loaded('phrasea2')) {
            throw new RuntimeException('Phrasea extension is required');
        }

        if (!function_exists('phrasea_conn')) {
            throw new RuntimeException('Phrasea extension requires upgrade');
        }

        if (phrasea_conn($hostname, $port, $user, $password, $dbname) !== true) {
            throw new RuntimeException('Unable to initialize Phrasea connection');
        }

        $this->initialized = true;

        return $this;
    }

    private function checkSession()
    {
        if (!$this->app['phraseanet.user']) {
            throw new \RuntimeException('Phrasea currently support only authenticated queries');
        }

        if (!phrasea_open_session($this->app['session']->get('phrasea_session_id'), $this->app['phraseanet.user']->get_id())) {
            if (!$ses_id = phrasea_create_session((string) $this->app['phraseanet.user']->get_id())) {
                throw new \Exception_InternalServerError('Unable to create phrasea session');
            }
            $this->app['session']->set('phrasea_session_id', $ses_id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function status()
    {
        $status = array();
        foreach (phrasea_info() as $key => $value) {
            $status[] = array($key, $value);
        }

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    public function configurationPanel()
    {
        if (!$this->configurationPanel) {
            $this->configurationPanel = new ConfigurationPanel($this);
        }

        return $this->configurationPanel;
    }

    /**
     * {@inheritdoc}
     */
    public function availableTypes()
    {
        return array(self::GEM_TYPE_RECORD, self::GEM_TYPE_STORY);
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord(\record_adapter $record)
    {
        return $this->updateRecord($record);
    }

    /**
     * {@inheritdoc}
     */
    public function removeRecord(\record_adapter $record)
    {
        $connbas = $record->get_databox()->get_connection();

        $sql = "DELETE FROM prop WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $record->get_record_id()));
        $stmt->closeCursor();

        $sql = "DELETE FROM idx WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $record->get_record_id()));
        $stmt->closeCursor();

        $sql = "DELETE FROM thit WHERE record_id = :record_id";
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $record->get_record_id()));
        $stmt->closeCursor();

        unset($stmt, $connbas);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function updateRecord(\record_adapter $record)
    {
        $record->set_binary_status(\databox_status::dec2bin($this->app, bindec($record->get_status()) & ~7 | 4));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addStory(\record_adapter $record)
    {
        return $this->updateRecord($record);
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
        throw new RuntimeException('Feed Entry indexing not supported by Phrasea Engine');
    }

    /**
     * {@inheritdoc}
     */
    public function removeFeedEntry(\Feed_Entry_Adapter $entry)
    {
        throw new RuntimeException('Feed Entry indexing not supported by Phrasea Engine');
    }

    /**
     * {@inheritdoc}
     */
    public function updateFeedEntry(\Feed_Entry_Adapter $entry)
    {
        throw new RuntimeException('Feed Entry indexing not supported by Phrasea Engine');
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(SearchEngineOptions $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function resetOptions()
    {
        $this->options = new SearchEngineOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, $offset, $perPage)
    {
        $this->initialize();
        $this->checkSession();

        assert(is_int($offset));
        assert($offset >= 0);
        assert(is_int($perPage));

        if (trim($query) === '') {
            $query = "all";
        }

        if ($this->options->getRecordType()) {
            $query .= ' AND recordtype=' . $this->options->getRecordType();
        }

        $sql = 'SELECT query, query_time, duration, total FROM cache WHERE session_id = :ses_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':ses_id' => $this->app['session']->get('phrasea_session_id')));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $date_obj = new \DateTime('-10 min');
        $date_quest = new \DateTime($row['query_time']);

        if ($query != $row['query']) {
            $this->resetCacheNextQuery = true;
        }
        if ($date_obj > $date_quest) {
            $this->resetCacheNextQuery = true;
        }

        if ($this->resetCacheNextQuery === true) {
            phrasea_clear_cache($this->app['session']->get('phrasea_session_id'));
            $this->addQuery($query);
            $this->executeQuery($query);

            $sql = 'SELECT query, query_time, duration, total FROM cache WHERE session_id = :ses_id';
            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute(array(':ses_id' => $this->app['session']->get('phrasea_session_id')));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        } else {
            /**
             * @todo clean this in DB
             */
            $this->total_available = $this->total_results = $this->app['session']->get('phrasea_engine_n_results');
        }

        $res = phrasea_fetch_results(
                $this->app['session']->get('phrasea_session_id'), $offset + 1, $perPage, false
        );

        $rs = array();
        $error = _('Unable to execute query');

        if (isset($res['results']) && is_array($res['results'])) {
            $rs = $res['results'];
            $error = '';
        }

        $resultNumber = $offset;
        $records = new ArrayCollection();

        foreach ($rs as $data) {
            try {
                $records->add(new \record_adapter(
                                $this->app,
                                \phrasea::sbasFromBas($this->app, $data['base_id']),
                                $data['record_id'],
                                $resultNumber
                ));
            } catch (Exception $e) {
                
            }
            $resultNumber++;
        }

        $propositions = $this->getPropositions();

        return new SearchEngineResult($records, $query, $row['duration'], $offset, $row['total'], $row['total'], $error, '', new ArrayCollection(), $propositions, '');
    }
    
    private function getPropositions()
    {
        if ($this->qp && isset($this->qp['main'])) {
            $proposals = self::proposalsToHTML($this->qp['main']->proposals);
            if (trim($proposals) !== '') {
                return "<div style='height:0px; overflow:hidden'>" . $this->qp['main']->proposals["QRY"]
                    . "</div><div class='proposals'>" . $proposals . "</div>";
            }
        }

        return null;
    }
    
    private static function proposalsToHTML($proposals)
    {
        $html = '';
        $b = true;
        foreach ($proposals["BASES"] as $zbase) {
            if ((int) (count($proposals["BASES"]) > 1) && count($zbase["TERMS"]) > 0) {
                $style = $b ? 'style="margin-top:0px;"' : '';
                $b = false;
                $html .= "<h1 $style>" . sprintf(_('reponses::propositions pour la base %s'), $zbase["NAME"]) . "</h1>";
            }
            $t = true;
            foreach ($zbase["TERMS"] as $path => $props) {
                $style = $t ? 'style="margin-top:0px;"' : '';
                $t = false;
                $html .= "<h2 $style>" . sprintf(_('reponses::propositions pour le terme %s'), $props["TERM"]) . "</h2>";
                $html .= $props["HTML"];
            }
        }

        return $html ;
    }

    public static function create(Application $app)
    {
        return new static($app);
    }

    /**
     * {@inheritdoc}
     */
    private function executeQuery($query)
    {
        $nbanswers = $total_time = 0;
        $sort = '';

        if ($this->options->sortBy()) {
            switch ($this->options->sortOrder()) {
                case SearchEngineOptions::SORT_MODE_ASC:
                    $sort = '+';
                    break;
                case SearchEngineOptions::SORT_MODE_DESC:
                default:
                    $sort = '-';
                    break;
            }
            $sort .= '0' . $this->options->sortBy();
        }

        foreach ($this->queries as $sbas_id => $qry) {
            $BF = array();

            foreach ($this->options->businessFieldsOn() as $collection) {
                $BF[] = $collection->get_base_id();
            }

            $results = phrasea_query2(
                    $this->app['session']->get('phrasea_session_id')
                    , $sbas_id
                    , $this->colls[$sbas_id]
                    , $this->arrayq[$sbas_id]
                    , $this->app['phraseanet.registry']->get('GV_sit')
                    , $this->app['session']->get('usr_id')
                    , false
                    , $this->options->searchType() == SearchEngineOptions::RECORD_GROUPING ? PHRASEA_MULTIDOC_REGONLY : PHRASEA_MULTIDOC_DOCONLY
                    , $sort
                    , $BF
            );

            if ($results) {
                $total_time += $results['time_all'];
                $nbanswers += $results["nbanswers"];
            }
        }

        $sql = 'UPDATE cache
                SET query = :query, query_time = NOW(), duration = :duration, total = :total
                WHERE session_id = :ses_id';

        $params = array(
            'query'     => $query,
            ':ses_id'   => $this->app['session']->get('phrasea_session_id'),
            ':duration' => $total_time,
            ':total'    => $nbanswers,
        );

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        if ($this->app['phraseanet.user']) {
            \User_Adapter::saveQuery($this->app, $query);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function autocomplete($query)
    {
        return new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function excerpt($query, $fields, \record_adapter $record)
    {
        $ret = array();

        $res = phrasea_fetch_results(
                $this->app['session']->get('phrasea_session_id'), ($record->get_number() + 1), 1, true, "[[em]]", "[[/em]]"
        );

        if (!isset($res['results']) || !is_array($res['results'])) {
            return array();
        }

        $rs = $res['results'];
        $res = array_shift($rs);
        if (!isset($res['xml'])) {
            return array();
        }

        $sxe = simplexml_load_string($res['xml']);

        foreach ($fields as $name => $field) {
            if ($sxe && $sxe->description && $sxe->description->$name) {
                $val = array();
                foreach ($sxe->description->$name as $value) {
                    $val[] = str_replace(array('[[em]]', '[[/em]]'), array('<em>', '</em>'), (string) $value);
                }
                $separator = $field['separator'] ? $field['separator'][0] : '';
                $val = implode(' ' . $separator . ' ', $val);
            } else {
                $val = $field['value'];
            }

            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function resetCache()
    {
        $this->resetCacheNextQuery = true;
        $this->queries = $this->arrayq = $this->colls = $this->qp = $this->needthesaurus = array();

        return $this;
    }

    private function addQuery($query)
    {
        foreach ($this->options->databoxes() as $databox) {
            $this->queries[$databox->get_sbas_id()] = $query;
        }

        $status = $this->options->getStatus();

        foreach ($this->queries as $sbas => $qs) {
            if ($status) {
                $requestStat = 'xxxx';

                for ($i = 4; ($i <= 32); $i++) {
                    if (!isset($status[$i])) {
                        $requestStat = 'x' . $requestStat;
                        continue;
                    }
                    $val = 'x';
                    if (isset($status[$i][$sbas])) {
                        if ($status[$i][$sbas] == '0') {
                            $val = '0';
                        } elseif ($status[$i][$sbas] == '1') {
                            $val = '1';
                        }
                    }
                    $requestStat = $val . $requestStat;
                }

                $requestStat = ltrim($requestStat, 'x');

                if ($requestStat !== '') {
                    $this->queries[$sbas] .= ' AND (recordstatus=' . $requestStat . ')';
                }
            }
            if ($this->options->fields()) {
                $this->queries[$sbas] .= ' IN (' . implode(' OR ', array_map(function(\databox_field $field) {
                                            return $field->get_name();
                                        }, $this->options->fields())) . ')';
            }
            if (($this->options->getMinDate() || $this->options->getMaxDate()) && $this->options->getDateFields()) {
                if ($this->options->getMinDate()) {
                    $this->queries[$sbas] .= ' AND ( ' . implode(' >= ' . $this->options->getMinDate()->format('Y-m-d') . ' OR  ', array_map(function(\databox_field $field){ return $field->get_name(); }, $this->options->getDateFields())) . ' >= ' . $this->options->getMinDate()->format('Y-m-d') . ' ) ';
                }
                if ($this->options->getMaxDate()) {
                    $this->queries[$sbas] .= ' AND ( ' . implode(' <= ' . $this->options->getMaxDate()->format('Y-m-d') . ' OR  ', array_map(function(\databox_field $field){ return $field->get_name(); }, $this->options->getDateFields())) . ' <= ' . $this->options->getMaxDate()->format('Y-m-d') . ' ) ';
                }
            }
        }

        $this->singleParse('main', $query);

        foreach ($this->queries as $sbas => $db_query) {
            $this->singleParse($sbas, $query);
        }

        $base_ids = array_map(function(\collection $collection) {
                    return $collection->get_base_id();
                }, $this->options->collections());

        foreach ($this->options->databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();

            $this->colls[$sbas_id] = array();

            foreach ($databox->get_collections() as $collection) {
                if (in_array($collection->get_base_id(), $base_ids)) {
                    $this->colls[$sbas_id][] = $collection->get_base_id();
                }
            }

            if (sizeof($this->colls[$sbas_id]) <= 0) {
                continue;
            }

            if ($this->needthesaurus[$sbas_id]) {
                if ($databox->get_dom_thesaurus()) {
                    $this->qp[$sbas_id]->thesaurus2($this->indep_treeq[$sbas_id], $sbas_id, $databox->get_dbname(), $databox->get_dom_thesaurus(), true);
                    $this->qp['main']->thesaurus2($this->indep_treeq['main'], $sbas_id, $databox->get_dbname(), $databox->get_dom_thesaurus(), true);
                }
            }

            $emptyw = false;

            $this->qp[$sbas_id]->set_default($this->indep_treeq[$sbas_id], $emptyw);
            $this->qp[$sbas_id]->distrib_in($this->indep_treeq[$sbas_id]);
            $this->qp[$sbas_id]->factor_or($this->indep_treeq[$sbas_id]);
            $this->qp[$sbas_id]->setNumValue($this->indep_treeq[$sbas_id], $databox->get_sxml_structure());
            $this->qp[$sbas_id]->thesaurus2_apply($this->indep_treeq[$sbas_id], $sbas_id);
            $this->arrayq[$sbas_id] = $this->qp[$sbas_id]->makequery($this->indep_treeq[$sbas_id]);
        }

        return $this;
    }

    private function singleParse($sbas, $query)
    {
        $this->qp[$sbas] = new PhraseaEngineQueryParser($this->app, $this->options->getLocale());
        $this->qp[$sbas]->debug = false;

        if ($sbas == 'main') {
            $simple_treeq = $this->qp[$sbas]->parsequery($query);
        } else {
            $simple_treeq = $this->qp[$sbas]->parsequery($this->queries[$sbas]);
        }

        $this->qp[$sbas]->priority_opk($simple_treeq);
        $this->qp[$sbas]->distrib_opk($simple_treeq);
        $this->needthesaurus[$sbas] = false;

        $this->indep_treeq[$sbas] = $this->qp[$sbas]->extendThesaurusOnTerms($simple_treeq, true, true, false);
        $this->needthesaurus[$sbas] = $this->qp[$sbas]->containsColonOperator($this->indep_treeq[$sbas]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearCache()
    {
        if ($this->app['session']->has('phrasea_session_id')) {
            $this->initialize();
            phrasea_close_session($this->app['session']->get('phrasea_session_id'));
            $this->app['session']->remove('phrasea_session_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function clearAllCache(\DateTime $date = null)
    {
        if (!$date) {
            $date = new \DateTime();
        }

        $sql = "SELECT session_id FROM cache WHERE lastaccess <= :date";

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':date' => $date->format(DATE_ISO8601)));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            phrasea_close_session($row['session_id']);
        }

        return $this;
    }

}
