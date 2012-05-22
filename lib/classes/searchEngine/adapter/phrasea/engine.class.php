<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class searchEngine_adapter_phrasea_engine extends searchEngine_adapter_abstract implements searchEngine_adapter_interface
{
    /**
     *
     * @var array
     */
    protected $queries = array();

    /**
     *
     * @var array
     */
    protected $colls = array();

    /**
     *
     * @var array
     */
    protected $qp = array();

    /**
     *
     * @var array
     */
    protected $answers = array();

    /**
     *
     * @var array
     */
    protected $needthesaurus = array();

    /**
     *
     * @var array
     */
    protected $indep_treeq = array();

    /**
     *
     * @var searchEngine_options
     */
    protected $options = false;

    /**
     *
     * @var array
     */
    protected $arrayq = array();

    /**
     *
     * @var array
     */
    protected $results = array();

    /**
     *
     * @var boolean
     */
    protected $reseted = false;

    /**
     *
     * @var int
     */
    protected $opt_search_type;

    /**
     *
     * @var array
     */
    protected $opt_bases = array();

    /**
     *
     * @var array
     */
    protected $opt_fields = array();

    /**
     *
     * @var array
     */
    protected $opt_status = array();

    /**
     *
     * @var array
     */
    protected $opt_date_field;

    /**
     *
     * @var DateTime
     */
    protected $opt_min_date;

    /**
     *
     * @var DateTime
     */
    protected $opt_max_date;

    /**
     *
     * @var string
     */
    protected $opt_record_type;

    /**
     *
     * @return searchEngine_adapter_phrasea_engine
     */
    public function __construct()
    {
        return $this;
    }

    /**
     *
     * @param  searchEngine_options                $options
     * @return searchEngine_adapter_phrasea_engine
     */
    public function set_options(searchEngine_options $options)
    {
        $this->options = $options;

        $this->opt_search_type = (int) $options->get_search_type();
        $this->opt_bases = $options->get_bases();
        $this->opt_fields = $options->get_fields();
        $this->opt_date_field = $options->get_date_fields();
        $this->opt_max_date = $options->get_max_date();
        $this->opt_min_date = $options->get_min_date();

        if (in_array($options->get_record_type(), array('image', 'video', 'audio', 'document', 'flash')))
            $this->opt_record_type = $options->get_record_type();

        foreach ($options->get_fields() as $field) {
            if (trim($field) === 'phraseanet--all--fields') {
                $this->opt_fields = array();
                break;
            }
        }

        $this->opt_status = $options->get_status();


        return $this;
    }

    /**
     *
     * @param  <type> $proposals
     * @return string
     */
    protected static function proposalsToHTML($proposals)
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
        $html .= '';

        return($html);
    }

    /**
     *
     * @return string
     */
    public function get_propositions()
    {
        if (isset($this->qp['main'])) {
            $proposals = self::proposalsToHTML($this->qp['main']->proposals);
            if (trim($proposals) !== '') {
                return "<div style='height:0px; overflow:hidden'>" . $this->qp['main']->proposals["QRY"]
                    . "</div><div class='proposals'>" . $proposals . "</div>";
            }
        }

        return null;
    }

    /**
     *
     * @param  int                  $query
     * @param  int                  $offset
     * @param  int                  $perPage
     * @return searchEngine_results
     */
    public function results($query, $offset, $perPage)
    {

        assert(is_int($offset));
        assert($offset >= 0);
        assert(is_int($perPage));

        $page = floor($offset / $perPage) + 1;

        $this->current_page = $page;
        $this->perPage = $perPage;

        $page = $this->get_current_page();

        if (trim($query) === '')
            $query = "all";
        if ($this->opt_record_type != '') {
            $query .= ' AND recordtype=' . $this->opt_record_type;
        }

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();

        $sql = 'SELECT query, query_time FROM cache WHERE session_id = :ses_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':ses_id' => $session->get_ses_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $date_obj = new DateTime('-10 min');
        $date_quest = new DateTime($row['query_time']);

        $reseted = $this->reseted;

        $reseted = false;
        if ($this->reseted)
            $reseted = true;
        if ($query != $row['query'])
            $reseted = true;
        if ($date_obj > $date_quest)
            $reseted = true;

        if ($reseted === true) {
            $this->reset_cache();
            self::addQuery($query);
            self::query();
        } else {
            $this->total_available = $this->total_results = $session->get_session_prefs('phrasea_engine_n_results');
        }

        $results = new set_result();


        $perPage = $this->get_per_page();
        $page = $this->get_current_page();
        $this->offset_start = $courcahnum = (($page - 1) * $perPage);

        $res = phrasea_fetch_results(
            $session->get_ses_id(), (int) (($page - 1) * $perPage) + 1, $perPage, false
        );

        $rs = array();
        if (isset($res['results']) && is_array($res['results']))
            $rs = $res['results'];

        foreach ($rs as $irec => $data) {
            try {
                $sbas_id = phrasea::sbasFromBas($data['base_id']);

                $record = new record_adapter(
                        $sbas_id,
                        $data['record_id'],
                        $courcahnum
                );

                $results->add_element($record);
            } catch (Exception $e) {

            }
            $courcahnum ++;
        }

        return new searchEngine_results($results, $this);
    }

    /**
     *
     * @return searchEngine_adapter_phrasea_engine
     */
    public function reset_cache()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        phrasea_clear_cache($session->get_ses_id());
        $this->reseted = true;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function get_status()
    {
        $infos = phrasea_info();

        $status = array();
        foreach ($infos as $key => $value) {
            $status[] = array($key, $value);
        }

        return $status;
    }

    /**
     *
     * @param  Session_Handler $session
     * @return array
     */
    public function get_suggestions(Session_Handler $session)
    {
        $props = array();
        foreach ($this->qp['main']->proposals['QUERIES'] as $prop) {
            $props[] = array(
                'value'   => $prop
                , 'current' => false
                , 'hits'    => null
            );
        }

        return $props;
    }

    /**
     *
     * @return string
     */
    public function get_parsed_query()
    {
        return $this->query;
    }

    /**
     *
     * @return searchEngine_adapter_phrasea_engine
     */
    protected function query()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        $dateLog = date("Y-m-d H:i:s");
        $nbanswers = 0;

        $sql = 'UPDATE cache SET query = :query, query_time = NOW()
            WHERE session_id = :ses_id';

        $params = array(
            'query'   => $this->get_parsed_query()
            , ':ses_id' => $session->get_ses_id()
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $total_time = 0;

        $sort = '';

        if ($this->options->get_sortby()) {
            switch ($this->options->get_sortord()) {
                case searchEngine_options::SORT_MODE_ASC:
                    $sort = '+';
                    break;
                case searchEngine_options::SORT_MODE_DESC:
                default:
                    $sort = '-';
                    break;
            }
            $sort .= '0' . $this->options->get_sortby();
        }

        foreach ($this->queries as $sbas_id => $qry) {
            $BF = array();

            foreach ($this->options->get_business_fields() as $base_id) {
                $BF[] = phrasea::collFromBas($base_id);
            }

            $this->results[$sbas_id] = phrasea_query2(
                $session->get_ses_id()
                , $sbas_id
                , $this->colls[$sbas_id]
                , $this->arrayq[$sbas_id]
                , $registry->get('GV_sit')
                , (string) $session->get_usr_id()
                , false
                , $this->opt_search_type == 1 ? PHRASEA_MULTIDOC_REGONLY : PHRASEA_MULTIDOC_DOCONLY
                , $sort
                , $BF
            );

            $total_time += $this->results[$sbas_id]['time_all'];

            if ($this->results[$sbas_id])
                $nbanswers += $this->results[$sbas_id]["nbanswers"];

            $logger = $session->get_logger(databox::get_instance($sbas_id));

            $conn2 = connection::getPDOConnection($sbas_id);

            $sql3 = "INSERT INTO log_search
               (id, log_id, date, search, results, coll_id )
               VALUES
               (null, :log_id, :date, :query, :nbresults, :colls)";

            $params = array(
                ':log_id'    => $logger->get_id()
                , ':date'      => $dateLog
                , ':query'     => $this->query
                , ':nbresults' => $this->results[$sbas_id]["nbanswers"]
                , ':colls'     => implode(',', $this->colls[$sbas_id])
            );

            $stmt = $conn2->prepare($sql3);
            $stmt->execute($params);
            $stmt->closeCursor();
        }

        $this->total_time = $total_time;

        User_Adapter::saveQuery($this->query);

        $session->set_session_prefs('phrasea_engine_n_results', $nbanswers);

        $this->total_available = $this->total_results = $nbanswers;

        return $this;
    }

    /**
     *
     * @param  int                                 $sbas
     * @return searchEngine_adapter_phrasea_engine
     */
    protected function singleParse($sbas)
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $this->qp[$sbas] = new searchEngine_adapter_phrasea_queryParser(Session_Handler::get_locale());
        $this->qp[$sbas]->debug = false;
        if ($sbas == 'main')
            $simple_treeq = $this->qp[$sbas]->parsequery($this->query);
        else
            $simple_treeq = $this->qp[$sbas]->parsequery($this->queries[$sbas]);

        $this->qp[$sbas]->priority_opk($simple_treeq);
        $this->qp[$sbas]->distrib_opk($simple_treeq);
        $this->needthesaurus[$sbas] = false;

        $this->indep_treeq[$sbas] = $this->qp[$sbas]->extendThesaurusOnTerms($simple_treeq, true, true, false);
        $this->needthesaurus[$sbas] = $this->qp[$sbas]->containsColonOperator($this->indep_treeq[$sbas]);

        return $this;
    }

    /**
     *
     * @param  string                              $query
     * @return searchEngine_adapter_phrasea_engine
     */
    protected function addQuery($query)
    {
        $qry = '';
        if (trim($query) != '') {
            $qry .= trim($query);
        }

        $appbox = appbox::get_instance(\bootstrap::getCore());

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $coll) {
                if (in_array($coll->get_base_id(), $this->opt_bases)) {
                    $this->queries[$databox->get_sbas_id()] = $qry;
                    break;
                }
            }
        }
        $this->query = $qry;

        foreach ($this->queries as $sbas => $qs) {
            if ($sbas === 'main')
                continue;
            if (count($this->opt_status) > 0) {
                $requestStat = 'xxxx';

                for ($i = 4; ($i <= 64); $i ++ ) {
                    if ( ! isset($this->opt_status[$i])) {
                        $requestStat = 'x' . $requestStat;
                        continue;
                    }
                    $set = false;
                    $val = '';
                    if (isset($this->opt_status[$i][$sbas]) && $this->opt_status[$i][$sbas] == '0') {
                        $set = true;
                        $val = '0';
                    }
                    if (isset($this->opt_status[$i][$sbas]) && $this->opt_status[$i][$sbas] == '1') {
                        if ($set)
                            $val = 'x';
                        else
                            $val = '1';
                    }
                    $requestStat = ( $val != '' ? $val : 'x' ) . $requestStat;
                }
                $requestStat = trim(ltrim($requestStat, 'x'));
                if ($requestStat !== '')
                    $this->queries[$sbas] .= ' and (recordstatus=' . $requestStat . ')';
            }
            if (count($this->opt_fields) > 0) {
                $this->queries[$sbas] .= ' dans (' . implode(' ou ', $this->opt_fields) . ')';
            }
            if (($this->opt_min_date || $this->opt_max_date) && $this->opt_date_field != '') {
                if ($this->opt_min_date)
                    $this->queries[$sbas] .= ' AND ( ' . implode(' >= ' . $this->opt_min_date->format('Y-m-d') . ' OR  ', $this->opt_date_field) . ' >= ' . $this->opt_min_date->format('Y-m-d') . ' ) ';
                if ($this->opt_max_date)
                    $this->queries[$sbas] .= ' AND ( ' . implode(' <= ' . $this->opt_max_date->format('Y-m-d') . ' OR  ', $this->opt_date_field) . ' <= ' . $this->opt_max_date->format('Y-m-d') . ' ) ';
            }
        }

        $this->singleParse('main');
        foreach ($this->queries as $sbas => $qryBas)
            $this->singleParse($sbas);

        foreach ($appbox->get_databoxes() as $databox) {
            if ( ! isset($this->queries[$databox->get_sbas_id()]))
                continue;

            //$databox = databox::get_instance($sbas_id);
            $sbas_id = $databox->get_sbas_id();
            $this->colls[$sbas_id] = array();
            foreach ($databox->get_collections() as $coll) {
                if (in_array($coll->get_base_id(), $this->opt_bases))
                    $this->colls[$sbas_id][] = (int) $coll->get_base_id();
            }
            if (sizeof($this->colls[$sbas_id]) <= 0)
                continue;
            if ($this->needthesaurus[$sbas_id]) {
                $domthesaurus = $databox->get_dom_thesaurus();

                if ($domthesaurus) {
                    $this->qp[$sbas_id]->thesaurus2($this->indep_treeq[$sbas_id], $sbas_id, $databox->get_dbname(), $domthesaurus, true);
                    $this->qp['main']->thesaurus2($this->indep_treeq['main'], $sbas_id, $databox->get_dbname(), $domthesaurus, true);
                }
            }

            if ($this->qp[$sbas_id]->errmsg != "") {
                $this->error .= ' ' . $this->qp[$sbas_id]->errmsg;
            }

            $emptyw = false;

            $this->qp[$sbas_id]->set_default($this->indep_treeq[$sbas_id], $emptyw);
            $this->qp[$sbas_id]->distrib_in($this->indep_treeq[$sbas_id]);
            $this->qp[$sbas_id]->factor_or($this->indep_treeq[$sbas_id]);
            $this->qp[$sbas_id]->setNumValue($this->indep_treeq[$sbas_id], $databox->get_sxml_structure());
            $this->qp[$sbas_id]->thesaurus2_apply($this->indep_treeq[$sbas_id], $sbas_id);
            $this->arrayq[$sbas_id] = $this->qp[$sbas_id]->makequery($this->indep_treeq[$sbas_id]);
            $this->results[$sbas_id] = NULL;
        }

        return $this;
    }

    public function build_excerpt($query, array $fields, record_adapter $record)
    {
        $ret = array();

        $appbox = appbox::get_instance(\bootstrap::getCore());
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
            if ($sxe->description->$name) {
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
}
