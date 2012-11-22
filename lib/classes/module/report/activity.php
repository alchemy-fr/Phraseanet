<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

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

    public function __construct(Application $app, $arg1, $arg2, $sbas_id, $collist)
    {
        parent::__construct($app, $arg1, $arg2, $sbas_id, $collist);
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

        for ($i = 0; $i < 24; $i ++) {
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

        $s = new module_report_sql($this->app, $this);

        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(), $filter['params']);

        $sql = "
        SELECT tt.heures, SUM(1) AS nb
        FROM (
            SELECT DISTINCT(log.id), DATE_FORMAT( log.date, '%k' ) AS heures
            FROM log FORCE INDEX (date_site)
            INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
            WHERE (" . $filter['sql'] . ")
        ) AS tt
        GROUP BY tt.heures
        ORDER BY tt.heures ASC";

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $res = $this->setDisplayForActivity($rs);

        $this->initDefaultConfigColumn($this->display);

        foreach ($rs as $row) {
            $row['heures'] = (string) $row['heures'];
            $res[$row['heures']] = round(($row['nb'] / 24), 2);

            if ($res[$row['heures']] < 1) {
                $res[$row['heures']] = number_format($res[$row['heures']], 2);
            } else {
                $res[$row['heures']] = (int) $res[$row['heures']];
            }
        }

        $this->result[] = $res;
        //calculate prev and next page
        $this->calculatePages($rs);
        //display navigator
        $this->setDisplayNav();
        //set report
        $this->setReport();

        $this->report['legend'] = range(0, 23);

        return $this->report;
    }

    /**
     * Get all questions by user
     *
     * @param string $value
     * @param string $what
     */
    public function getAllQuestionByUser($value, $what)
    {
        $result = array();

        $s = new module_report_sql($this->app, $this);

        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(':main_value' => $value), $filter['params']);

        $sql = "
        SELECT DATE_FORMAT(log_search.date,'%Y-%m-%d %H:%i:%S') AS date ,
        log_search.search ,log_search.results
        FROM (log_search)
            INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_search.log_id)
            INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
        WHERE (" . $filter['sql'] . ")
        AND log.`" . $what . "` = :main_value
        ORDER BY date ";

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $s->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $sql .= $s->getFilters()->getLimitFilter();

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->initDefaultConfigColumn($this->champ);
        $i = 0;

        foreach ($rs as $row) {
            foreach ($this->champ as $value) {
                $result[$i][$value] = $row[$value];
            }
            $i++;
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

        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(), $filter['params']);

        ($no_answer) ? $this->title = _('report:: questions sans reponses') : $this->title = _('report:: questions les plus posees');

        $sql = "
        SELECT TRIM(tt.search) AS search, SUM(1) AS nb, ROUND(avg(tt.results)) AS nb_rep
        FROM (
            SELECT DISTINCT(log.id), log_search.search AS search, results
            FROM (log_search)
                INNER JOIN log FORCE INDEX (date_site) ON (log_search.log_id = log.id)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
            WHERE (" . $filter['sql'] . ")
            AND log_search.search != 'all' " .
            ($no_answer ? ' AND log_search.results = 0 ' : '') . "
        ) AS tt
        GROUP BY tt.search
        ORDER BY nb DESC";

        $sql .= !$no_answer ? ' LIMIT ' . $this->nb_top : '';

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        $i = 0;
        foreach ($rs as $row) {
            foreach ($this->champ as $value) {
                $this->result[$i][$value] = $row[$value];
            }

            $i++;

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
        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(), $filter['params']);
        $databox = $this->app['phraseanet.appbox']->get_databox($this->sbas_id);

        $sql = "
        SELECT log_docs.record_id, log_docs.date, log_docs.final AS objets
        FROM (`log_docs`)
        INNER JOIN log FORCE INDEX (date_site) ON (log_docs.log_id = log.id)
        INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
        INNER JOIN record ON (log_docs.record_id = record.record_id)
        WHERE (". $filter['sql'] .") AND log_docs.action = 'download'
        ORDER BY date DESC";

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $s->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $sql .= $s->getFilters()->getLimitFilter() ?: '';

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $login = User_Adapter::getInstance($usr, $this->app)->get_display_name();

        $this->setChamp($rs);

        $config ? $this->setConfigColumn($config) : $this->initDefaultConfigColumn($this->champ);

        $i = 0;
        foreach ($rs as $row) {
            $record = $databox->get_record($row['record_id']);

            foreach ($this->champ as $value) {
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

        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(), $filter['params']);

        $sql = "
            SELECT tt.record_id, DATE_FORMAT(tt.the_date, GET_FORMAT(DATE, 'INTERNAL')) AS ddate, tt.final, SUM(1) AS nb
            FROM (
                SELECT DISTINCT(log.id), log_docs.date AS the_date, log_docs.final, log_docs.record_id
                FROM (log_docs)
                    INNER JOIN record ON (record.record_id = log_docs.record_id)
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE (" . $filter['sql'] . ")
                    AND (log_docs.action =  'download' OR log_docs.action =  'mail')
                    AND (log_docs.final = 'preview' OR log_docs.final = 'document')
            ) AS tt
            LEFT JOIN subdef AS s ON (s.record_id = tt.record_id)
            WHERE s.name = tt.final
            GROUP BY tt.final, ddate
            ORDER BY tt.the_date DESC";

        $stmt = $s->getConnBas()->prepare($sql);
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
            $date = $this->app['date-formatter']->getPrettyString(new DateTime($row['ddate']));
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
                $this->result[$i]['document'] += $row['nb'];
                $total['tot_doc'] += $row['nb'];
            } else {
                $this->result[$i]['preview'] += $row['nb'];
                $total['tot_prev'] += $row['nb'];
            }

            $this->result[$i]['total'] += $row['nb'];

            $total['tot_dl'] += $row['nb'];
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

        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(), $filter['params']);

        $this->req = "
            SELECT SUM(1) AS connexion, tt.user, tt.usrid FROM (
                SELECT
                    DISTINCT(log.id),
                    log." . $on . " AS " . $on . ",
                    log.usrid
                FROM log FORCE INDEX (date_site)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE log.user != 'API'
                AND (" . $filter['sql'] . ")
            ) AS tt
            GROUP BY tt.usrid
            ORDER BY connexion DESC ";

        $stmt = $s->getConnBas()->prepare($this->req);
        $stmt->execute($params);
        $s->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $this->enable_limit ? $this->req .= "LIMIT 0," . $this->nb_record : "";

        $stmt = $s->getConnBas()->prepare($this->req);
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
     * Get the deail of download by users
     *
     * @param array  $tab config for the html table
     * @param String $on
     *
     * @return array
     */
    public function getDetailDownload($tab = false, $on = "")
    {
        empty($on) ? $on = "user" : ""; //by default always report on user

        //set title
        $this->title = _('report:: Detail des telechargements');

        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters()->getReportFilter();
        $params = array_merge(array(), $filter['params']);

        $sql = "
            SELECT tt.usrid, TRIM(" . $on . ") AS " . $on . ", tt.final, sum(1) AS nb, sum(size) AS poid
            FROM (
                SELECT DISTINCT(log.id), TRIM(" . $on . ") AS " . $on . ", log_docs.record_id, log_docs.final, log.usrid
                FROM log_docs
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                    INNER JOIN record ON (record.record_id = log_docs.record_id)
                WHERE (" . $filter['sql'] . ")
                AND (log_docs.action = 'download' OR log_docs.action = 'mail')
            ) AS tt
            LEFT JOIN subdef FORCE INDEX (unicite) ON (tt.record_id = subdef.record_id)
            WHERE subdef.name = tt.final
            GROUP BY " . $on . ", usrid
            ORDER BY nb DESC;";

        $stmt =  $s->getConnBas()->prepare($sql);
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

    public static function topTenUser(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $result['top_ten_doc'] = array();
        $result['top_ten_prev'] = array();
        $result['top_ten_poiddoc'] = array();
        $result['top_ten_poidprev'] = array();

        $params = array(':site_id' => $app['phraseanet.registry']->get('GV_sit'));

        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $params = array_merge($params, $datefilter['params']);

        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);
        $params = array_merge($params, $collfilter['params']);

        $sql = "SELECT tt.usrid, tt.user, tt.final, tt.record_id, SUM(1) AS nb, SUM(size) AS poid
                FROM (
                    SELECT DISTINCT(log.id), log.usrid, user, final, log_date.record_id
                    FROM (log_docs AS log_date)
                        INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_date.log_id)
                        INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                        WHERE log.site = :site_id
                        AND log_date.action = 'download'
                        AND (" . $datefilter['sql'] . ")
                        AND (" . $collfilter['sql'] . ")
                ) AS tt
                LEFT JOIN subdef AS s ON (s.record_id = tt.record_id)
                WHERE s.name = tt.final
                GROUP BY tt.user, tt.final";

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

    public static function activity(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $res = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array(':site_id' => $app['phraseanet.registry']->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
        SELECT tt.id, HOUR(tt.heures) AS heures
        FROM (
            SELECT DISTINCT(log_date.id), log_date.date AS heures
            FROM log AS log_date FORCE INDEX (date_site)
            INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
            WHERE " . $datefilter['sql'] . "
            AND " . $collfilter['sql'] . "
            AND log_date.site = :site_id
        ) AS tt";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $stmt->rowCount();
        $stmt->closeCursor();

        for ($i = 0; $i < 24; $i ++ ){
            $res[$i] = 0;
        }

        foreach ($rs as $row) {
            if ($total > 0) {
                $res[$row["heures"]]++;
            }
        }

        foreach ($res as $heure => $value) {
            $res[$heure] = number_format(($value / 24), 2, '.', '');
        }

        return $res;
    }

    public static function activityDay(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $res = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array(':site_id' => $app['phraseanet.registry']->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity
            FROM (
                SELECT DISTINCT(log_date.id), DATE_FORMAT( log_date.date, '%Y-%m-%d' ) AS ddate
                FROM log AS log_date FORCE INDEX (date_site) INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id
                AND (" . $collfilter['sql'] . ")
            ) AS tt
            GROUP by  tt.ddate
            ORDER BY  tt.ddate ASC";

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

    public static function activityQuestion(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array(':site_id' => $app['phraseanet.registry']->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
                SELECT tt.usrid, tt.user, sum(1) AS nb
                FROM (
                    SELECT DISTINCT(log_date.id), log_date.usrid, log_date.user
                    FROM (`log_search`)
                        INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_search.log_id = log_date.id)
                        INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                    WHERE " . $datefilter['sql'] . "
                    AND log_date.site = :site_id
                    AND (" . $collfilter['sql'] . ")
                ) AS tt
                GROUP BY tt.usrid
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

    public static function activiteTopQuestion(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter =
            module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array(':site_id' => $app['phraseanet.registry']->get('GV_sit'));
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT TRIM(tt.search) AS question, tt.usrid, tt.user, SUM(1) AS nb
            FROM (
                SELECT DISTINCT(log_date.id), log_search.search, log_date.usrid, log_date.user
                FROM (`log_search`)
                    INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_search.log_id = log_date.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id
                AND (" . $collfilter['sql'] . ")
            ) AS tt
            GROUP BY tt.search
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

    public static function activiteTopTenSiteView(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT tt.referrer, SUM(1) AS nb_view
            FROM (
                SELECT DISTINCT(log_date.id), referrer
                FROM (log_view)
                    INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_view.log_id = log_date.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "
                AND (" . $collfilter['sql'] . ")
            ) AS tt
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

    public static function activiteAddedDocument(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity
            FROM (
                SELECT DISTINCT(log.id), DATE_FORMAT(log_date.date, '%Y-%m-%d') AS ddate
                FROM (log_docs AS log_date)
                    INNER JOIN log FORCE INDEX (date_site) ON (log_date.log_id = log.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . " AND log_date.action = 'add'
                AND (" . $collfilter['sql'] . ")
            ) AS tt
            GROUP BY tt.ddate
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

    public static function activiteEditedDocument(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity
            FROM (
                SELECT DISTINCT(log.id), DATE_FORMAT( log_date.date, '%Y-%m-%d') AS ddate
                FROM (log_docs AS log_date)
                    INNER JOIN log FORCE INDEX (date_site) ON (log_date.log_id = log.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . " AND log_date.action = 'edit'
                AND (" . $collfilter['sql'] . ")
            ) AS tt
            GROUP BY tt.ddate
            ORDER BY activity ASC ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $date = $app['date-formatter']->getPrettyString(new DateTime($row['ddate']));
            $result[$date] = $row['activity'];
        }

        return $result;
    }

    public static function activiteAddedTopTenUser(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $result = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array();
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $sql = "
            SELECT tt.usrid, tt.user, sum( 1 ) AS nb
            FROM (
                SELECT DISTINCT(log.id), log.usrid, log.user
                FROM (log_docs AS log_date)
                INNER JOIN log FORCE INDEX (date_site) ON (log_date.log_id = log.id)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . " AND log_date.action = 'add'
                AND (" . $collfilter['sql'] . ")
            ) AS tt
            GROUP BY tt.usrid
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
