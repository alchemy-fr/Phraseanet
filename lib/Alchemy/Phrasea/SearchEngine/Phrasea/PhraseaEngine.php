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

use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class PhraseaEngine implements SearchEngineInterface
{
    /**
     *
     * @var SearchEngineOptions
     */
    protected $options;
    protected $queries = array();
    protected $arrayq = array();
    protected $colls = array();
    protected $qp = array();
    protected $needthesaurus = array();
    protected $configurationPanel;
    protected $resetCacheNextQuery = false;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->options = new SearchEngineOptions();
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

    public function getConfigurationPanel(Application $app, Request $request)
    {

    }

    public function postConfigurationPanel(Application $app, Request $request)
    {

    }

    private function configurationPanel()
    {
        if ( ! $this->configurationPanel) {
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
        $record->set_binary_status(\databox_status::dec2bin(bindec($record->get_status()) & ~7 | 4));

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
        assert(is_int($offset));
        assert($offset >= 0);
        assert(is_int($perPage));

        if (trim($query) === '') {
            $query = "all";
        }

        if ($this->options->getRecordType()) {
            $query .= ' AND recordtype=' . $this->options->getRecordType();
        }

        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();

        $sql = 'SELECT query, query_time, duration, total FROM cache WHERE session_id = :ses_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':ses_id' => $session->get_ses_id()));
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
            phrasea_clear_cache($session->get_ses_id());
            $this->addQuery($query);
            $this->executeQuery($query);

            $sql = 'SELECT query, query_time, duration, total FROM cache WHERE session_id = :ses_id';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ses_id' => $session->get_ses_id()));
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        } else {
            /**
             * @todo clean this in DB
             */
            $this->total_available = $this->total_results = $session->get_session_prefs('phrasea_engine_n_results');
        }

        $res = phrasea_fetch_results(
            $session->get_ses_id(), $offset + 1, $perPage, false
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
                        \phrasea::sbasFromBas($data['base_id']),
                        $data['record_id'],
                        $resultNumber
                ));
            } catch (Exception $e) {

            }
            $resultNumber ++;
        }


        return new SearchEngineResult($records, $query, $row['duration'], $offset, $row['total'], $row['total'], $error, '', new ArrayCollection(), new ArrayCollection(), '');
    }

    /**
     * {@inheritdoc}
     */
    private function executeQuery($query)
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        $dateLog = date("Y-m-d H:i:s");
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
                $session->get_ses_id()
                , $sbas_id
                , $this->colls[$sbas_id]
                , $this->arrayq[$sbas_id]
                , $registry->get('GV_sit')
                , (string) $session->get_usr_id()
                , false
                , $this->options->searchType() == SearchEngineOptions::RECORD_GROUPING ? PHRASEA_MULTIDOC_REGONLY : PHRASEA_MULTIDOC_DOCONLY
                , $sort
                , $BF
            );

            if ($results) {
                $total_time += $results['time_all'];
                $nbanswers += $results["nbanswers"];
            }

            $logger = $session->get_logger($appbox->get_databox($sbas_id));

            $conn2 = \connection::getPDOConnection($sbas_id);

            $sql3 = "INSERT INTO log_search
               (id, log_id, date, search, results, coll_id )
               VALUES
               (null, :log_id, :date, :query, :nbresults, :colls)";

            $params = array(
                ':log_id'    => $logger->get_id()
                , ':date'      => $dateLog
                , ':query'     => $query
                , ':nbresults' => $results["nbanswers"]
                , ':colls'     => implode(',', $this->colls[$sbas_id])
            );

            $stmt = $conn2->prepare($sql3);
            $stmt->execute($params);
            $stmt->closeCursor();
        }

        $sql = 'UPDATE cache
                SET query = :query, query_time = NOW(), duration = :duration, total = :total
                WHERE session_id = :ses_id';

        $params = array(
            'query'     => $query,
            ':ses_id'   => $session->get_ses_id(),
            ':duration' => $total_time,
            ':total'    => $nbanswers,
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        \User_Adapter::saveQuery($query);

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

        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $res = phrasea_fetch_results(
            $session->get_ses_id(), ($record->get_number() + 1), 1, true, "[[em]]", "[[/em]]"
        );

        if ( ! isset($res['results']) || ! is_array($res['results'])) {
            return array();
        }

        $rs = $res['results'];
        $res = array_shift($rs);
        if ( ! isset($res['xml'])) {
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

                for ($i = 4; ($i <= 64); $i ++ ) {
                    if ( ! isset($status[$i])) {
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
                $this->queries[$sbas] .= ' IN (' . implode(' OR ', $this->options->fields()) . ')';
            }
            if (($this->options->getMinDate() || $this->options->getMaxDate()) && $this->options->getDateFields()) {
                if ($this->options->getMinDate()) {
                    $this->queries[$sbas] .= ' AND ( ' . implode(' >= ' . $this->options->getMinDate()->format('Y-m-d') . ' OR  ', $this->options->getDateFields()) . ' >= ' . $this->options->getMinDate()->format('Y-m-d') . ' ) ';
                }
                if ($this->options->getMaxDate()) {
                    $this->queries[$sbas] .= ' AND ( ' . implode(' <= ' . $this->options->getMaxDate()->format('Y-m-d') . ' OR  ', $this->options->getDateFields()) . ' <= ' . $this->options->getMaxDate()->format('Y-m-d') . ' ) ';
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
        $this->qp[$sbas] = new PhraseaEngineQueryParser($this->options->getLocale());
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
}

