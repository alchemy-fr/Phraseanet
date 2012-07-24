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
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_activity extends module_report
{
    /**
     * Number of questions displayed in the most asked questions
     * @var int
     */
    private $nb_top = 20;

    /**
     *   Array correspondance column -> to query
     * @var array
     */
    protected $cor_query = array(
        'user'      => 'log.user',
        'site'      => 'log.site',
        'societe'   => 'log.societe',
        'pays'      => 'log.pays',
        'activite'  => 'log.activite',
        'fonction'  => 'log.fonction',
        'usrid'     => 'log.usrid',
        'coll_id'   => 'record.coll_id',
        'ddate'     => "DATE_FORMAT(log.date, '%Y-%m-%d')",
        'id'        => 'log_docs.id',
        'log_id'    => 'log_docs.log_id',
        'record_id' => 'log_docs.record_id',
        'final'     => 'log_docs.final',
        'comment'   => 'log_docs.comment',
        'size'      => 'subdef.size'
    );

    public function __construct($arg1, $arg2, $sbas_id, $collist)
    {
        parent::__construct($arg1, $arg2, $sbas_id, $collist);
    }

    /**
     * set top value
     * @param  int                    $nb_top
     * @return module_report_activity
     */
    public function setTop($nb_top)
    {
        $this->nb_top = $nb_top;

        return $this;
    }

    /**
     * get Top value
     * @return int
     */
    public function getTop()
    {
        return $this->nb_top;
    }

    private function setDisplayForActivity($rs)
    {
        $hours = array();

        for ($i = 0; $i < 24; $i ++ ) {
            array_push($this->display, $i);
            $hours[$i] = 0;
        }

        if (count($rs) > 0) {
            $row = array_shift($rs);
            $this->champ = array_merge($this->champ, array_keys($row));
        }

        return $hours;
    }

    /**
     * @desc get the site activity per hours
     * @return array
     */
    public function getActivityPerHours()
    {
        $this->result = array();
        $this->title = _('report:: activite par heure');

        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);

        $sql = "
                SELECT DATE_FORMAT( log.date, '%k' ) AS heures, SUM(1) AS nb
                FROM log
                WHERE (" . $date_filter['sql'] . ")
                AND (" . $coll_filter['sql'] . ")
                AND " . $site_filter['sql'] . "
                GROUP BY heures
                ORDER BY heures ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $res = $this->setDisplayForActivity($rs);

        $this->initDefaultConfigColumn($this->display);

        foreach ($rs as $row) {
            $row['heures'] = (string) $row['heures'];
            $res[$row['heures']] = round(($row['nb'] / 24), 2);
            if ($res[$row['heures']] < 1)
                $res[$row['heures']] = number_format($res[$row['heures']], 2);
            else
                $res[$row['heures']] = (int) $res[$row['heures']];
        }

        $this->result[] = $res;
        //calculate prev and next page
        $this->calculatePages($rs);
        //do we display navigator ?
        $this->setDisplayNav();
        //set report
        $this->setReport();

        $this->report['legend'] = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12,
            13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23);

        return $this->report;
    }

    /**
     * @desc get all questions by user
     * @param string $idUser
     */
    public function getAllQuestionByUser($value, $what)
    {
        $result = array();

        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        $params = array(':main_value' => $value);
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);

        $sql = "
                SELECT DATE_FORMAT(log_search.date,'%Y-%m-%d %H:%i:%S') as date ,
                log_search.search ,log_search.results
                FROM (log_search inner join log on log.id = log_search.log_id)
                WHERE log_search.date > " . $date_filter['sql'] . "
                AND log.`" . $what . "` = :main_value
                AND " . $site_filter['sql'] . "
                AND (" . $coll_filter['sql'] . ")
                ORDER BY date";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $s->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $sql .= $filter->getLimitFilter();

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->initDefaultConfigColumn($this->champ);
        $i = 0;

        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value)
                $result[$i][$value] = $row[$value];
            $i ++;
        }

        $this->title = _('report:: questions');
        $this->setResult($result);

        return $this->result;
    }

    /**
     * get the most asked question
     * @param array $tab       config for html table
     * @param bool  $no_answer true for question with no answer
     */
    public function getTopQuestion($tab = false, $no_answer = false)
    {
        $this->report['value'] = array();
        $this->report['value2'] = array();

        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        $i = 0;
        ($no_answer) ? $this->title = _('report:: questions sans reponses') :
                $this->title = _('report:: questions les plus posees');

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);

        $sql = "
                SELECT TRIM(log_search.search) as search,
                    SUM(1) as nb,
                    ROUND(avg(results)) as nb_rep
                FROM (log_search inner join log on log_search.log_id = log.id)
                WHERE " . $date_filter['sql'] . "
                AND log_search.search != 'all'
                AND (" . $coll_filter['sql'] . ")";

        ($no_answer) ? $sql .= " AND log_search.results = 0 " : "";

        $sql .= "
                GROUP BY log_search.search
                ORDER BY nb DESC";

        ( ! $no_answer) ? $sql .= " LIMIT 0," . $this->nb_top : "";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value)
                $this->result[$i][$value] = $row[$value];
            $i ++;
            $this->report['legend'][] = $row['search'];
            $this->report['value'][] = $row['nb'];
            $this->report['value2'][] = $row['nb_rep'];
        }

        $this->total = sizeof($this->result);
        //calculate prev and next page
        $this->calculatePages($rs);
        //do we display navigator ?
        $this->setDisplayNav();
        //set report
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc get all downloads from one specific user
     * @param $usr user id
     * @param  array $config config for the html table
     * @return array
     */
    public function getAllDownloadByUserBase($usr, $config = false)
    {
        $result = array();
        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        $databox = \databox::get_instance($this->sbas_id);

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);
        $user_filter = $filter->getUserIdFilter($usr);
        $params = array_merge($params, $user_filter['params']);

        $sql = "
                SELECT log_docs.record_id,
                    log_docs.date, log_docs.final as objets
                FROM (`log_docs` inner join log on log_docs.log_id = log.id
                    inner join record on log_docs.record_id = record.record_id)
                WHERE log_docs.action = 'download'
                AND " . $date_filter['sql'] . "
                AND " . $user_filter['sql'] . "
                AND " . $site_filter['sql'] . "
                AND (" . $coll_filter['sql'] . ")";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $s->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $sql .= "
                ORDER BY date DESC";
        $sql .= $filter->getLimitFilter();

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $login = User_Adapter::getInstance($usr, appbox::get_instance(\bootstrap::getCore()))->get_display_name();

        $this->setChamp($rs);
        ($config) ? $this->setConfigColumn($config) :
                $this->initDefaultConfigColumn($this->champ);
        $i = 0;

        foreach ($rs as $row) {
            $record = $databox->get_record($row['record_id']);

            foreach ($this->champ as $key => $value) {
                $result[$i][$value] = $row[$value];
            }

            $result[$i]['titre'] = $record->get_title();

            $i ++;
        }
        $this->title = sprintf(_('report:: Telechargement effectue par l\'utilisateur %s'), $login);
        $this->setResult($result);

        return $this->result;
    }

    /**
     * @desc get all download by base by day
     * @param  array $tab config for html table
     * @return array
     */
    public function getDownloadByBaseByDay($tab = false)
    {
        $this->title = _('report:: telechargements par jour');

        $registry = registry::get_instance();

        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);
        $record_filter = $filter->getUserFilter();
        if ($record_filter)
            $params = array_merge($params, $record_filter['params']);

        $sql = "
            SELECT
              log_docs.date
             AS ddate,
                 final
            FROM (
            log_docs
            INNER JOIN record ON record.record_id = log_docs.record_id
            INNER JOIN log ON " . $site_filter['sql'] . "
            AND log.id = log_docs.log_id
            LEFT JOIN subdef AS s ON s.record_id = log_docs.record_id
              AND s.name = log_docs.final)
            WHERE " . $date_filter['sql'] . "
                AND (log_docs.final != 'caption')
                AND log_docs.action =  'download'
                AND (" . $coll_filter['sql'] . ")";
        if ($record_filter['sql'])
            $sql .= "AND (" . $record_filter['sql'] . ")";
        $sql .= ' ORDER BY log_docs.date DESC';

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);
        $save_date = "";
        $total = array('tot_doc'  => 0, 'tot_prev' => 0, 'tot_dl'   => 0);
        $i = -1;

        $last_date = null;

        foreach ($rs as $row) {
            $date = phraseadate::getPrettyString(new DateTime($row['ddate']));
            if ($date != $last_date) {
                $i ++;
                $this->result[$i] = array(
                    'ddate'    => $date,
                    'document' => 0,
                    'preview'  => 0,
                    'total'    => 0
                );
                $last_date = $date;
            }

            if ($row['final'] == 'document') {
                $this->result[$i]['document'] += 1;
                $total['tot_doc'] += 1;
            } else {
                $this->result[$i]['preview'] += 1;
                $total['tot_prev'] += 1;
            }

            $this->result[$i]['total'] += 1;

            $total['tot_dl'] += 1;
        }

        $nb_row = $i + 1;
        $s->setTotalrows($nb_row);

        if ($s->getTotalRows() > 0) {
            $this->result[$nb_row]['ddate'] = '<b>TOTAL</b>';
            $this->result[$nb_row]['document'] = '<b>' . $total['tot_doc'] . '</b>';
            $this->result[$nb_row]['preview'] = '<b>' . $total['tot_prev'] . '</b>';
            $this->result[$nb_row]['total'] = '<b>' . $total['tot_dl'] . '</b>';
        }
        $this->calculatePages($rs);
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc get nb connexion by user , fonction ,societe etc..
     * @param  array  $tab config for html table
     * @param  string $on  choose the field on what you want the result
     * @return array
     */
    public function getConnexionBase($tab = false, $on = "")
    {
        //default group on user column
        if (empty($on)) {
            $on = "user";
        }

        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);

        $this->req = "
                SELECT  DISTINCT(log." . $on . ") as " . $on . ",
                    usrid,
                    SUM(1) as connexion
                FROM log
                WHERE log.user != 'API'
                AND " . $site_filter['sql'] . "
                AND " . $date_filter['sql'] . "
                AND (" . $coll_filter['sql'] . ")
                GROUP BY " . $on . "
                ORDER BY connexion DESC ";

        $stmt = $conn->prepare($this->req);
        $stmt->execute($params);
        $s->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $this->enable_limit ? $this->req .= "LIMIT 0," . $this->nb_record : "";

        $stmt = $conn->prepare($this->req);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $i = 0;
        $total_connexion = 0;
        //set title
        $this->title = _('report:: Detail des connexions');
        //set champ
        $this->champ = array($on, 'connexion');
        //set display
        $this->default_display = array($on, 'connexion');
        //set configuration of column
        ($tab) ? $this->setConfigColumn($tab) :
                $this->initDefaultConfigColumn($this->default_display);
        //build result
        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = empty($row[$value]) ?
                    "<i>" . _('report:: non-renseigne') . "</i>" : $row[$value];

                if ($value == 'connexion')
                    $total_connexion += $row['connexion'];
            }
            $this->result[$i]['usrid'] = $row['usrid'];
            $i ++;
            if ($i >= $this->nb_record)
                break;
        }

        $this->total = $i ++;

        if ($this->total > 0) {
            $this->result[$i]['usrid'] = 0;
            $this->result[$i]['connexion'] = '<b>' . $total_connexion . '</b>';
            $this->result[$i][$on] = '<b>TOTAL</b>';
        }
        //calculate prev and next page
        $this->calculatePages($rs);
        //do we display navigator ?
        $this->setDisplayNav();
        //set report
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc get the deail of download by users
     * @param  bool  $ext false for your appbox conn, true for external connections
     * @param  array $tab config for the html table
     * @return array
     */
    public function getDetailDownload($tab = false, $on = "")
    {
        empty($on) ? $on = "user" : ""; //by default always report on user

        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();

        //set title
        $this->title = _('report:: Detail des telechargements');

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);
        $record_filter = $filter->getRecordFilter();
        $params = array_merge($params, $record_filter['params']);

        $sql = "
                SELECT
                    usrid,
                    TRIM(" . $on . ") as " . $on . ",
                    final, sum(1) as nb,
                    sum(size) as poid
                FROM (log_docs as d
                INNER JOIN log ON " . $site_filter['sql'] . "
                    AND log.id = d.log_id
                    AND " . $date_filter['sql'] . "
                INNER JOIN record ON record.record_id = d.record_id
                LEFT JOIN subdef as s on (d.action = 'download' OR d.action = 'mail')
                    AND s.record_id=d.record_id and s.name=d.final
                )
                WHERE (" . $coll_filter['sql'] . ")
                    AND (" . $record_filter['sql'] . ")
                GROUP BY " . $on . ", final, usrid
                WITH rollup";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $save_user = "";
        $i = -1;
        $total = array(
            'nbdoc'    => 0,
            'poiddoc'  => 0,
            'nbprev'   => 0,
            'poidprev' => 0
        );

        $this->setChamp($rs);

        $this->setDisplay($tab);

        foreach ($rs as $row) {
            $user = $row[$on];
            if (($save_user != $user) && ! is_null($user) && ! empty($user)) {
                if ($i >= 0) {
                    if (($this->result[$i]['nbprev'] + $this->result[$i]['nbdoc']) == 0 || ($this->result[$i]['poiddoc'] + $this->result[$i]['poidprev']) == 0) {
                        unset($this->result[$i]);
                    }

                    if (isset($this->result[$i]['poiddoc']) && isset($this->result[$i]['poidprev'])) {
                        $this->result[$i]['poiddoc'] = p4string::format_octets($this->result[$i]['poiddoc']);
                        $this->result[$i]['poidprev'] = p4string::format_octets($this->result[$i]['poidprev']);
                    }
                }

                $i ++;

                $this->result[$i]['nbprev'] = 0;
                $this->result[$i]['poidprev'] = 0;
                $this->result[$i]['nbdoc'] = 0;
                $this->result[$i]['poiddoc'] = 0;
            }

            //doc info
            if ($row['final'] == 'document' &&
                ! is_null($user) && ! is_null($row['usrid'])) {
                $this->result[$i]['nbdoc'] = ( ! is_null($row['nb']) ? $row['nb'] : 0);
                $this->result[$i]['poiddoc'] = ( ! is_null($row['poid']) ? $row['poid'] : 0);
                $this->result[$i]['user'] = empty($row[$on]) ?
                    "<i>" . _('report:: non-renseigne') . "</i>" : $row[$on];
                $total['nbdoc'] += $this->result[$i]['nbdoc'];
                $total['poiddoc'] += ( ! is_null($row['poid']) ? $row['poid'] : 0);
                $this->result[$i]['usrid'] = $row['usrid'];
            }
            //preview info
            if (($row['final'] == 'preview' || $row['final'] == 'thumbnail') &&
                ! is_null($user) &&
                ! is_null($row['usrid'])) {

                $this->result[$i]['nbprev'] += ( ! is_null($row['nb']) ? $row['nb'] : 0);
                $this->result[$i]['poidprev'] += ( ! is_null($row['poid']) ? $row['poid'] : 0);

                $this->result[$i]['user'] = empty($row[$on]) ?
                    "<i>" . _('report:: non-renseigne') . "</i>" : $row[$on];
                $total['nbprev'] += ( ! is_null($row['nb']) ? $row['nb'] : 0);
                $total['poidprev'] += ( ! is_null($row['poid']) ? $row['poid'] : 0);
                $this->result[$i]['usrid'] = $row['usrid'];
            }

            $save_user = $user;
        }

        unset($this->result[$i]);
        $nb_row = $i + 1;
        $this->total = $nb_row;

        if ($this->total > 0) {
            $this->result[$nb_row]['user'] = '<b>TOTAL</b>';
            $this->result[$nb_row]['nbdoc'] = '<b>' . $total['nbdoc'] . '</b>';
            $this->result[$nb_row]['poiddoc'] =
                '<b>' . p4string::format_octets($total['poiddoc']) . '</b>';
            $this->result[$nb_row]['nbprev'] = '<b>' . $total['nbprev'] . '</b>';
            $this->result[$nb_row]['poidprev'] =
                '<b>' . p4string::format_octets($total['poidprev']) . '</b>';
        }
        $this->total = sizeof($this->result);
        $this->calculatePages($rs);
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    public function getPush($tab = false)
    {
        $s = new module_report_sql($this);
        $filter = $s->getFilters();
        $conn = $s->getConnBas();
        $push = array();

        $params = array();
        $date_filter = $filter->getDateFilter();
        $params = array_merge($params, $date_filter['params']);
        $coll_filter = $filter->getCollectionFilter();
        $params = array_merge($params, $coll_filter['params']);
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($params, $site_filter['params']);
        $record_filter = $filter->getRecordFilter();
        $params = array_merge($params, $record_filter['params']);

        $sql = "
            SELECT log.usrid, log.user , d.final as getter,  d.record_id, d.date, s.*
            FROM (log_docs as d
            INNER JOIN log ON (" . $site_filter['sql'] . "
                AND log.id = d.log_id
                AND " . $date_filter['sql'] . ")
            INNER JOIN record ON (record.record_id = d.record_id)
            LEFT JOIN subdef as s ON (s.record_id=d.record_id and s.name='document'))
            WHERE ((" . $coll_filter['sql'] . ")
                AND " . $record_filter['sql'] . "
                AND d.action='push')
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->initDefaultConfigColumn($this->champ);

        $appbox = appbox::get_instance(\bootstrap::getCore());

        $i = 0;
        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = $row[$value];
                if ($value == "getter") {
                    try {
                        $user = User_Adapter::getInstance($row[$value], $appbox);
                        $this->result[$i][$value] = $user->get_display_name();
                    } catch (Exception $e) {

                    }
                } elseif ($value == "size") {
                    $this->result[$i][$value] = p4string::format_octets($row[$value]);
                } elseif ($value == "date") {
                    $date_obj = new DateTime($row[$value]);
                    $this->result[$i][$value] = phraseadate::getPrettyString($date_obj);
                }
            }
            $i ++;
        }

        $this->total = sizeof($this->result);
        //calculate prev and next page
        $this->calculatePages($rs);
        //do we display navigator ?
        $this->setDisplayNav();
        //set report
        $this->setReport();

        return($this->report);
    }

    public static function topTenUser($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $registry = registry::get_instance();
        $result = array();
        $result['top_ten_doc'] = array();
        $result['top_ten_prev'] = array();
        $result['top_ten_poiddoc'] = array();
        $result['top_ten_poidprev'] = array();

        $params = array(':site_id' => $registry->get('GV_sit'));

        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $params = array_merge($params, $datefilter['params']);

        $collfilter = module_report_sqlfilter::constructCollectionFilter($list_coll_id);
        $params = array_merge($params, $collfilter['params']);

        $sql = "
                SELECT log.usrid, user, final, sum(1) AS nb, sum(size) AS poid
                FROM (log_docs AS log_date
                INNER JOIN log ON log.site = :site_id
                AND log.id = log_date.log_id
                AND " . $datefilter['sql'] . ")
                LEFT JOIN subdef AS s ON log_date.action = 'download'
                AND s.record_id = log_date.record_id
                AND s.name = log_date.final
                AND (" . $collfilter['sql'] . ")
                GROUP BY user, final
                WITH rollup";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $save_id = "";
        foreach ($rs as $row) {
            $kttd = 'top_ten_doc';
            $kttp = 'top_ten_poiddoc';

            $kttpr = 'top_ten_prev';
            $kttpp = 'top_ten_poidprev';

            $id = $row['usrid'];

            if ( ! is_null($row['usrid'])
                && ! is_null($row['user'])
                && ! is_null($row['final']) && ! is_null($row['nb'])
                && ! is_null($row['poid'])) {
                if ($row['final'] == 'document') {
                    $result[$kttd][$id]['lib'] = $row['user'];
                    $result[$kttd][$id]['id'] = $id;
                    $result[$kttd][$id]['nb'] = ! is_null($row['nb']) ?
                        (int) $row['nb'] : 0;
                    $result[$kttp][$id]['nb'] = ! is_null($row['poid']) ?
                        (int) $row['poid'] : 0;
                    $result[$kttp][$id]['lib'] = $row['user'];
                    $result[$kttp][$id]['id'] = $id;
                    if ( ! isset($result[$kttd][$id]['nb']))
                        $result[$kttd][$id]['nb'] = 0;
                }
                if ($row['final'] == 'preview') {
                    $result[$kttpr][$id]['lib'] = $row['user'];
                    $result[$kttpr][$id]['id'] = $id;
                    if ( ! isset($result[$kttpr][$id]['nb']))
                        $result[$kttpr][$id]['nb'] = 0;
                    $result[$kttpr][$id]['nb'] = ! is_null($row['nb']) ?
                        (int) $row['nb'] : 0;
                    $result[$kttpp][$id]['nb'] = ! is_null($row['poid']) ?
                        (int) $row['poid'] : 0;
                    $result[$kttpp][$id]['lib'] = $row['user'];
                    $result[$kttpp][$id]['id'] = $id;
                }
            }
            $save_id = $id;
        }

        return $result;
    }

    public static function activity($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $registry = registry::get_instance();
        $res = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array(':site_id' => $registry->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
                SELECT log_date.id, HOUR(log_date.date) as heures
                FROM log as log_date
                WHERE " . $datefilter['sql'] . "
                AND (" . $collfilter['sql'] . ")
                AND log_date.site = :site_id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $stmt->rowCount();
        $stmt->closeCursor();

        for ($i = 0; $i < 24; $i ++ )
            $res[$i] = 0;

        foreach ($rs as $row) {
            if ($total > 0)
                $res[$row["heures"]] ++;
        }

        foreach ($res as $heure => $value)
            $res[$heure] = number_format(($value / 24), 2, '.', '');

        return $res;
    }

    public static function activityDay($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $registry = registry::get_instance();
        $result = array();
        $res = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array(':site_id' => $registry->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT DISTINCT (
                DATE_FORMAT( log_date.date, '%Y-%m-%d' )
                ) AS ddate, COUNT( DATE_FORMAT( log_date.date, '%d' ) ) AS activity
                FROM log as log_date
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id
                AND (" . $collfilter['sql'] . ")
                GROUP by ddate
                ORDER BY ddate ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $date = new DateTime($row['ddate']);
            $result[$date->format(DATE_ATOM)] = $row['activity'];
        }

        foreach ($result as $key => $act) {
            $res[$key] = number_format($act, 2, '.', '');
        }

        return $res;
    }

    public static function activityQuestion($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $registry = registry::get_instance();
        $result = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array(':site_id' => $registry->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
                SELECT log_date.usrid, log_date.user, sum(1) AS nb
                FROM `log_search`
                INNER JOIN log as log_date
                ON log_search.log_id = log_date.id
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id
                AND (" . $collfilter['sql'] . ")
                GROUP BY log_date.usrid
                ORDER BY nb DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $result[$row['usrid']]['lib'] = $row['user'];
            $result[$row['usrid']]['nb'] = (int) $row['nb'];
            $result[$row['usrid']]['id'] = $row['usrid'];
        }

        return $result;
    }

    public static function activiteTopQuestion($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $registry = registry::get_instance();
        $result = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array(':site_id' => $registry->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT
                TRIM(log_search.search) as question,
                log_date.usrid,
                log_date.user,
                sum(1) AS nb
            FROM `log_search`
            INNER JOIN log as log_date
            ON log_search.log_id = log_date.id
            WHERE " . $datefilter['sql'] . "
            AND log_date.site = :site_id
            AND (" . $collfilter['sql'] . ")
            GROUP BY log_search.search
            ORDER BY nb DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $conv = array(" " => "");
        foreach ($rs as $row) {
            $question = $row['question'];
            $question = mb_strtolower(strtr($question, $conv));
            $result[$question]['lib'] = $row['question'];
            $result[$question]['nb'] = (int) $row['nb'];
            $result[$question]['id'] = "false";
        }

        return $result;
    }

    public static function activiteTopTenSiteView($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT referrer, COUNT(referrer) as nb_view
            FROM log_view
            INNER JOIN log as log_date
            ON log_view.log_id = log_date.id
            WHERE " . $datefilter['sql'] . "
            AND (" . $collfilter['sql'] . ")
            GROUP BY referrer
            ORDER BY nb_view DESC ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            ($row['referrer'] != 'NO REFERRER') ?
                    $host = parent::getHost($row['referrer']) :
                    $host = 'NO REFERRER';
            if ( ! isset($result[$host]['nb']))
                $result[$host]['nb'] = 0;
            if ( ! isset($result[$host]['lib']))
                $result[$host]['lib'] = $host;
            $result[$host]['nb']+= ( (int) $row['nb_view']);
            $result[$host]['id'] = "false";
        }

        return $result;
    }

    public static function activiteAddedDocument($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT DISTINCT (
            DATE_FORMAT( log_date.date, '%Y-%m-%d'  )
            ) AS ddate, COUNT( DATE_FORMAT( log_date.date, '%d' ) ) AS activity
            FROM log_docs as log_date
            INNER JOIN log
            ON log_date.log_id = log.id
            WHERE " . $datefilter['sql'] . " AND log_date.action = 'add'
            AND (" . $collfilter['sql'] . ")
            GROUP BY ddate
            ORDER BY activity ASC ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        foreach ($rs as $row) {
            $date = new DateTime($row['ddate']);
            $result[$date->format(DATE_ATOM)] = $row['activity'];
        }

        return $result;
    }

    public static function activiteEditedDocument($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT DISTINCT (
            DATE_FORMAT( log_date.date, '%Y-%m-%d' )
            ) AS ddate, COUNT( DATE_FORMAT( log_date.date, '%d' ) ) AS activity
            FROM log_docs as log_date
            INNER JOIN log
            ON log_date.log_id = log.id
            WHERE " . $datefilter['sql'] . " AND log_date.action = 'edit'
            AND (" . $collfilter['sql'] . ")
            GROUP BY ddate
            ORDER BY activity ASC ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $date = phraseadate::getPrettyString(new DateTime($row['ddate']));
            $result[$date] = $row['activity'];
        }

        return $result;
    }

    public static function activiteAddedTopTenUser($dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT log.usrid, log.user, sum( 1 ) AS nb
            FROM log_docs as log_date
            INNER JOIN log ON log_date.log_id = log.id
            WHERE " . $datefilter['sql'] . " AND log_date.action = 'add'
            AND (" . $collfilter['sql'] . ")
            GROUP BY log.usrid
            ORDER BY nb ASC ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $result[$row['usrid']]['lib'] = $row['user'];
            $result[$row['usrid']]['nb'] = $row['nb'];
            $result[$row['usrid']]['id'] = $row['usrid'];
        }

        return $result;
    }
}
