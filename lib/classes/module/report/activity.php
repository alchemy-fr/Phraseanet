<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
    protected $cor_query = [
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
    ];

    public function __construct(Application $app, $arg1, $arg2, $sbas_id, $collist)
    {
        // parent::__construct($app, $arg1, $arg2, $sbas_id, $collist);
        parent::__construct($app, $arg1, $arg2, $sbas_id, "");
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
        $hours = [];

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

    // ==================== Site activity : Site activity =====================
    /**
     * @desc get the site activity per hours
     * @return array
     */
    public function getActivityPerHours()
    {
        $this->result = [];
        $this->title = $this->app->trans('report:: activite par heure');

        $sqlBuilder = new module_report_sql($this->app, $this);

        $filter = $sqlBuilder->getFilters()->getReportFilter();
        $params = array_merge([], $filter['params']);

        $sql = "
            SELECT CAST(DATE_FORMAT(log.date, '%k') AS UNSIGNED) AS heures, COUNT(id) AS nb
                FROM log FORCE INDEX (date_site)
                WHERE (" . $filter['sql'] . ") AND !ISNULL(usrid)
            GROUP BY heures;";

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
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
        $this->calculatePages();
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
        $result = [];

        $sqlBuilder = new module_report_sql($this->app, $this);

        $filter = $sqlBuilder->getFilters()->getReportFilter();
        $params = array_merge([':main_value' => $value], $filter['params']);

        $sql = "
            SELECT DATE_FORMAT(log_search.date,'%Y-%m-%d %H:%i:%S') AS date ,
            log_search.search ,log_search.results
            FROM (log_search)
                INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_search.log_id)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
            WHERE (" . $filter['sql'] . ")
            AND log.`" . $what . "` = :main_value
            ORDER BY date ";

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $sqlBuilder->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $sql .= $sqlBuilder->getFilters()->getLimitFilter();

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
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

        $this->title = $this->app->trans('report:: questions');
        $this->setResult($result);

        return $this->result;
    }

    // ================== Site activity : Top questions (le second radio ...) ================
    /**
     * get the most asked question
     * @param array $tab       config for html table
     * @param bool  $no_answer true for question with no answer
     */
    public function getTopQuestion($tab = false, $no_answer = false)
    {
        $this->report['value'] = [];
        $this->report['value2'] = [];

        $this->setDateField('log_search.date');
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters()->getReportFilter();
        $params = array_merge([], $filter['params']);

        ($no_answer) ? $this->title = $this->app->trans('report:: questions sans reponses') : $this->title = $this->app->trans('report:: questions les plus posees');

        $sql = "
                SELECT TRIM(log_search.search) AS search, COUNT(log_search.id) AS nb, ROUND(avg(results)) AS nb_rep
                FROM (log_search)
                    INNER JOIN log FORCE INDEX (date_site) ON (log_search.log_id = log.id)
                WHERE (" . $filter['sql'] . ") AND !ISNULL(usrid)
                AND log_search.search != 'all' " .
            ($no_answer ? ' AND log_search.results = 0 ' : '') . "

            GROUP BY search
            ORDER BY nb DESC";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $sql .= !$no_answer ? ' LIMIT ' . $this->nb_top : '';

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
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
        $this->calculatePages();
        //do we display navigator ?
        $this->setDisplayNav();
        //set report
        $this->setReport();

        return $this->report;
    }

    // ============================ Downloads : Daily ==========================
    /**
     * @desc get all download by base by day
     * @param  array $tab config for html table
     * @return array
     */
    public function getDownloadByBaseByDay($tab = false)
    {
        $this->title = $this->app->trans('report:: telechargements par jour');
        $this->setDateField('log_docs.date');
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters()->getReportFilter();
        $params = array_merge([], $filter['params']);

        $sql = "
            SELECT tt.record_id, tt.the_date AS ddate, tt.final, SUM(1) AS nb
            FROM (
                SELECT DISTINCT(log.id), log_docs.date AS the_date, log_docs.final, log_docs.record_id
                FROM (log_docs)
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)
                    LEFT JOIN record ON (log_docs.record_id = record.record_id)
                WHERE (" . $filter['sql'] . ") AND !ISNULL(usrid)
                    AND (log_docs.action =  'download' OR log_docs.action =  'mail')
                    AND (log_docs.final = 'preview' OR log_docs.final = 'document')
            ) AS tt
            GROUP BY tt.final, ddate
            ORDER BY tt.the_date DESC";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);
        $total = ['tot_doc'  => 0, 'tot_prev' => 0, 'tot_dl'   => 0];
        $i = -1;

        $last_date = null;

        foreach ($rs as $row) {
            $date = $this->app['date-formatter']->getPrettyString(new DateTime($row['ddate']));
            if ($date != $last_date) {
                $i ++;
                $this->result[$i] = [
                    'ddate'    => $date,
                    'document' => 0,
                    'preview'  => 0,
                    'total'    => 0
                ];
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
        $sqlBuilder->setTotalrows($nb_row);

        if ($sqlBuilder->getTotalRows() > 0) {
            $this->result[$nb_row]['ddate'] = '<b>TOTAL</b>';
            $this->result[$nb_row]['document'] = '<b>' . $total['tot_doc'] . '</b>';
            $this->result[$nb_row]['preview'] = '<b>' . $total['tot_prev'] . '</b>';
            $this->result[$nb_row]['total'] = '<b>' . $total['tot_dl'] . '</b>';
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, var_export($this->result, true)), FILE_APPEND);
        foreach($this->result as $k=>$row) {
            $_row = array();
            foreach((array) $tab as $k2=>$f) {
                $_row[$k2] = array_key_exists($k2, $row) ? $row[$k2] : '';
            }
            $this->result[$k] = $_row;
        }
// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, var_export($this->result, true)), FILE_APPEND);

        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    // ==================== Connections: Per users =====================
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

        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters()->getReportFilter();
        $params = array_merge([], $filter['params']);

        $this->req = "
            SELECT COUNT(id) AS connexion, log.user, log.usrid
                FROM log FORCE INDEX (date_site)
                WHERE log.user != 'API'
                AND (" . $filter['sql'] . ") AND !ISNULL(usrid)
            GROUP BY usrid
            ORDER BY connexion DESC ";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $this->req), FILE_APPEND);

        $stmt = $sqlBuilder->getConnBas()->prepare($this->req);
        $stmt->execute($params);
        $sqlBuilder->setTotalrows($stmt->rowCount());
        $stmt->closeCursor();

        $this->enable_limit ? $this->req .= " LIMIT 0," . $this->nb_record : "";

        $stmt = $sqlBuilder->getConnBas()->prepare($this->req);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $i = 0;
        $total_connexion = 0;
        //set title
        $this->title = $this->app->trans('report:: Detail des connexions');
        //set champ
        $this->champ = [$on, 'connexion'];
        //set display
        $this->default_display = [$on, 'connexion'];
        //set configuration of column
        ($tab) ? $this->setConfigColumn($tab) :
                $this->initDefaultConfigColumn($this->default_display);
        //build result
        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = empty($row[$value]) ?
                    "<i>" . $this->app->trans('report:: non-renseigne') . "</i>" : $row[$value];

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
        $this->calculatePages();
        //do we display navigator ?
        $this->setDisplayNav();
        //set report
        $this->setReport();

        return $this->report;
    }

    // ========================= Downloads : Per users =====================
    /**
     * Get the detail of download by users
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
        $this->title = $this->app->trans('report:: Detail des telechargements');

        $this->setDateField('log_docs.date');
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters()->getReportFilter();
        $params = array_merge([], $filter['params']);

        $sql = "
                SELECT TRIM(" . $on . ") AS " . $on . ", SUM(1) AS nb, log_docs.final, log.usrid
                FROM log_docs
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)
                    LEFT JOIN record ON (record.record_id = log_docs.record_id)
                WHERE (" . $filter['sql'] . ") AND !ISNULL(usrid)
                AND (log_docs.action = 'download' OR log_docs.action = 'mail')
                AND (log_docs.final = 'preview' OR log_docs.final = 'document')
                GROUP BY usrid";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt =  $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $save_user = "";
        $i = -1;
        $total = [
            'nbdoc'    => 0,
            'nbprev'   => 0,
        ];

        $this->setChamp($rs);

        $this->setDisplay($tab);

        foreach ($rs as $row) {
            $user = $row[$on];
            if (($save_user != $user) && ! is_null($user) && ! empty($user)) {
                if ($i >= 0) {
                    if (($this->result[$i]['nbprev'] + $this->result[$i]['nbdoc']) == 0) {
                        unset($this->result[$i]);
                    }
                }

                $i ++;

                $this->result[$i]['nbprev'] = 0;
                $this->result[$i]['nbdoc'] = 0;
            }

            //doc info
            if ($row['final'] == 'document' &&
                ! is_null($user) && ! is_null($row['usrid'])) {
                $this->result[$i]['nbdoc'] = ( ! is_null($row['nb']) ? $row['nb'] : 0);
                $this->result[$i]['user'] = empty($row[$on]) ?
                    "<i>" . $this->app->trans('report:: non-renseigne') . "</i>" : $row[$on];
                $total['nbdoc'] += $this->result[$i]['nbdoc'];
                $this->result[$i]['usrid'] = $row['usrid'];
            }
            //preview info
            if (($row['final'] == 'preview') &&
                ! is_null($user) &&
                ! is_null($row['usrid'])) {

                $this->result[$i]['nbprev'] += ( ! is_null($row['nb']) ? $row['nb'] : 0);

                $this->result[$i]['user'] = empty($row[$on]) ?
                    "<i>" . $this->app->trans('report:: non-renseigne') . "</i>" : $row[$on];
                $total['nbprev'] += ( ! is_null($row['nb']) ? $row['nb'] : 0);
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
            $this->result[$nb_row]['nbprev'] = '<b>' . $total['nbprev'] . '</b>';
        }

        foreach($this->result as $k=>$row) {
            $_row = array();
            foreach((array) $tab as $k2=>$f) {
                $_row[$k2] = array_key_exists($k2, $row) ? $row[$k2] : '';
            }
            $_row['usrid'] = array_key_exists('usrid', $row) ? $row['usrid'] : '';
            $this->result[$k] = $_row;
        }

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s) %s\n\n", __FILE__, __LINE__, var_export($this->result, true)), FILE_APPEND);

        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    // ========================== ???????????????? ===========================
    public static function topTenUser(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $result['top_ten_doc'] = [];
        $result['top_ten_prev'] = [];
        $result['top_ten_poiddoc'] = [];
        $result['top_ten_poidprev'] = [];

        $params = [':site_id' => $app['conf']->get(['main', 'key'])];

        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax, 'log_docs.date');
        $params = array_merge($params, $datefilter['params']);
/*
        $sql = "SELECT tt.usrid, tt.user, tt.final, tt.record_id, SUM(1) AS nb, SUM(size) AS poid
                FROM (
                    SELECT DISTINCT(log.id), log.usrid, user, final, log_date.record_id
                    FROM (log_docs AS log_date)
                        INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_date.log_id)
                        INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                        WHERE log.site = :site_id
                        AND log_date.action = 'download'
                        AND (" . $datefilter['sql'] . ")" .
            (('' !== $collfilter['sql']) ?  "AND (" . $collfilter['sql'] . ")" : '')
            . "
                ) AS tt
                LEFT JOIN subdef AS s ON (s.record_id = tt.record_id)
                WHERE s.name = tt.final
                GROUP BY tt.user, tt.final";
*/
        $sql = "SELECT tt.usrid, tt.user, tt.final, tt.record_id, SUM(1) AS nb, SUM(size) AS poid\n"
            . " FROM (\n"
            . "        SELECT DISTINCT(log.id), log.usrid, user, final, log_docs.record_id\n"
            . "        FROM (log_docs)\n"
            . "            INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_docs.log_id)\n"
            . "            WHERE log.site = :site_id\n"
            . "            AND log_docs.action = 'download'\n"
            . "            AND (" . $datefilter['sql'] . ")\n"
            . ") AS tt\n"
            . "LEFT JOIN subdef AS s ON (s.record_id = tt.record_id)\n"
            . "WHERE s.name = tt.final\n"
            . "GROUP BY tt.user, tt.final";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

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
        }

        return $result;
    }

    //============================= Dashboard =========================
    public static function activity(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $res = [];
        $datefilter =
            module_report_sqlfilter::constructDateFilter($dmin, $dmax);

        $params = [':site_id' => $app['conf']->get(['main', 'key'])];
        $params = array_merge($params, $datefilter['params']);
/*
        $sql = "
            SELECT tt.id, HOUR(tt.heures) AS heures
            FROM (
                SELECT DISTINCT(log_date.id), log_date.date AS heures
                FROM log AS log_date FORCE INDEX (date_site)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "" .
            (('' !== $collfilter['sql']) ?  "AND (" . $collfilter['sql'] . ")" : '')
            . " AND log_date.site = :site_id
            ) AS tt";
*/
        $sql = "SELECT tt.id, HOUR(tt.heures) AS heures\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log_date.id), log_date.date AS heures\n"
            . "     FROM log AS log_date FORCE INDEX (date_site)\n"
            . "     WHERE " . $datefilter['sql'] . " AND !ISNULL(usrid)"
            . " AND log_date.site = :site_id\n"
            . " ) AS tt";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $stmt->rowCount();
        $stmt->closeCursor();

        for ($i = 0; $i < 24; $i ++) {
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

    //============================= Dashboard =========================
    public static function activityDay(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = array();
        $res = array();
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);

        $params = [':site_id' => $app['conf']->get(['main', 'key'])];
        $params = array_merge($params, $datefilter['params']);
/*
        $sql = "
            SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity
            FROM (
                SELECT DISTINCT(log_date.id), DATE_FORMAT( log_date.date, '%Y-%m-%d' ) AS ddate
                FROM log AS log_date FORCE INDEX (date_site) INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id" .
            (('' !== $collfilter['sql']) ?  (" AND (" . $collfilter['sql'] . ")") : '')
            . ") AS tt
            GROUP by  tt.ddate
            ORDER BY  tt.ddate ASC";
*/
        $sql = "SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log_date.id), DATE_FORMAT( log_date.date, '%Y-%m-%d' ) AS ddate\n"
            . "     FROM log AS log_date FORCE INDEX (date_site)\n"
            . "     WHERE " . $datefilter['sql'] . "\n"
            . "     AND log_date.site = :site_id AND !ISNULL(usrid)"
            . ") AS tt\n"
            . " GROUP by  tt.ddate\n"
            . " ORDER BY  tt.ddate ASC";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

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

    //============================= Dashboard =========================
    public static function activityQuestion(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);

        $params = [':site_id' => $app['conf']->get(['main', 'key'])];
        $params = array_merge($params, $datefilter['params']);

/*
        $sql = "
            SELECT tt.usrid, tt.user, sum(1) AS nb
            FROM (
                SELECT DISTINCT(log_date.id), log_date.usrid, log_date.user
                FROM (`log_search`)
                    INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_search.log_id = log_date.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id" .
            (('' !== $collfilter['sql']) ?  " AND (" . $collfilter['sql'] . ")" : '')
            . ") AS tt
            GROUP BY tt.usrid
            ORDER BY nb DESC";
*/
        $sql = "SELECT tt.usrid, tt.user, sum(1) AS nb\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log_date.id), log_date.usrid, log_date.user\n"
            . "     FROM (`log_search`)\n"
            . "         INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_search.log_id = log_date.id)\n"
            . "     WHERE " . $datefilter['sql'] . "\n"
            . "     AND log_date.site = :site_id"
            . ") AS tt\n"
            . " GROUP BY tt.usrid\n"
            . " ORDER BY nb DESC";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

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

    //============================= Dashboard =========================
    public static function activiteTopQuestion(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);

        $params = [':site_id' => $app['conf']->get(['main', 'key'])];
        $params = array_merge($params, $datefilter['params']);

/*
        $sql = "
            SELECT TRIM(tt.search) AS question, tt.usrid, tt.user, SUM(1) AS nb
            FROM (
                SELECT DISTINCT(log_date.id), log_search.search, log_date.usrid, log_date.user
                FROM (`log_search`)
                    INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_search.log_id = log_date.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "
                AND log_date.site = :site_id" .
            (('' !== $collfilter['sql']) ?  " AND (" . $collfilter['sql'] . ")" : '')
            . ") AS tt
            GROUP BY tt.search
            ORDER BY nb DESC";
*/
        $sql = "SELECT TRIM(tt.search) AS question, tt.usrid, tt.user, SUM(1) AS nb\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log_date.id), log_search.search, log_date.usrid, log_date.user\n"
            . "     FROM (`log_search`)\n"
            . "         INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_search.log_id = log_date.id)\n"
            . "     WHERE " . $datefilter['sql'] . "\n"
            . "     AND log_date.site = :site_id"
            . ") AS tt\n"
            . " GROUP BY tt.search\n"
            . " ORDER BY nb DESC";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $conv = [" " => ""];
        foreach ($rs as $row) {
            $question = $row['question'];
            $question = mb_strtolower(strtr($question, $conv));
            $result[$question]['lib'] = $row['question'];
            $result[$question]['nb'] = (int) $row['nb'];
            $result[$question]['id'] = "false";
        }

        return $result;
    }

    //============================= Dashboard =========================
    public static function activiteTopTenSiteView(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);

        $params = [];
        $params = array_merge($params, $datefilter['params']);


/*
        $sql = "
            SELECT tt.referrer, SUM(1) AS nb_view
            FROM (
                SELECT DISTINCT(log_date.id), referrer
                FROM (log_view)
                    INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_view.log_id = log_date.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . "" .
            (('' !== $collfilter['sql']) ?  " AND (" . $collfilter['sql'] . ")" : '')
            . ") AS tt
            GROUP BY referrer
            ORDER BY nb_view DESC ";
*/
        $sql = "SELECT tt.referrer, SUM(1) AS nb_view\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log_date.id), referrer\n"
            . "     FROM (log_view)\n"
            . "        INNER JOIN log AS log_date FORCE INDEX (date_site) ON (log_view.log_id = log_date.id)\n"
            . "     WHERE " . $datefilter['sql']
            . ") AS tt\n"
            . " GROUP BY referrer\n"
            . " ORDER BY nb_view DESC ";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

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

    //============================= Dashboard =========================
    public static function activiteAddedDocument(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax, 'log_docs.date');
        $params = [];
        $params = array_merge($params, $datefilter['params']);

/*
        $sql = "
            SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity
            FROM (
                SELECT DISTINCT(log.id), DATE_FORMAT(log_date.date, '%Y-%m-%d') AS ddate
                FROM (log_docs AS log_date)
                    INNER JOIN log FORCE INDEX (date_site) ON (log_date.log_id = log.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . " AND log_date.action = 'add' " .
            (('' !== $collfilter['sql']) ?  " AND (" . $collfilter['sql'] . ")" : '')
            . "
            ) AS tt
            GROUP BY tt.ddate
            ORDER BY activity ASC ";
*/
        $sql = "SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log.id), DATE_FORMAT(log_docs.date, '%Y-%m-%d') AS ddate\n"
            . "     FROM (log_docs)\n"
            . "         INNER JOIN log FORCE INDEX (date_site) ON (log_docs.log_id = log.id)\n"
            . "     WHERE " . $datefilter['sql'] . " AND log_docs.action = 'add'"
            . " ) AS tt\n"
            . " GROUP BY tt.ddate\n"
            . " ORDER BY activity ASC ";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

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

    //============================= Dashboard =========================
    public static function activiteEditedDocument(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax, 'log_docs.date');
        $params = [];
        $params = array_merge($params, $datefilter['params']);

/*
        $sql = "
            SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity
            FROM (
                SELECT DISTINCT(log.id), DATE_FORMAT( log_date.date, '%Y-%m-%d') AS ddate
                FROM (log_docs AS log_date)
                    INNER JOIN log FORCE INDEX (date_site) ON (log_date.log_id = log.id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . " AND log_date.action = 'edit'" .
            (('' !== $collfilter['sql']) ?  " AND (" . $collfilter['sql'] . ")" : '')
            . ") AS tt
            GROUP BY tt.ddate
            ORDER BY activity ASC ";
*/
        $sql = "SELECT tt.ddate, COUNT( DATE_FORMAT( tt.ddate, '%d' ) ) AS activity\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log.id), DATE_FORMAT( log_docs.date, '%Y-%m-%d') AS ddate\n"
            . "     FROM (log_docs)\n"
            . "         INNER JOIN log FORCE INDEX (date_site) ON (log_docs.log_id = log.id)\n"
            . "     WHERE " . $datefilter['sql'] . " AND log_docs.action = 'edit'"
            . ") AS tt\n"
            . " GROUP BY tt.ddate\n"
            . " ORDER BY activity ASC ";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

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

    //============================= Dashboard =========================
    public static function activiteAddedTopTenUser(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $databox = $app->findDataboxById($sbas_id);
        $conn = $databox->get_connection();
        $result = [];
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax, 'log_docs.date');

        $params = [];
        $params = array_merge($params, $datefilter['params']);
/*
        $sql = "
            SELECT tt.usrid, tt.user, sum( 1 ) AS nb
            FROM (
                SELECT DISTINCT(log.id), log.usrid, log.user
                FROM (log_docs AS log_date)
                INNER JOIN log FORCE INDEX (date_site) ON (log_date.log_id = log.id)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE " . $datefilter['sql'] . " AND log_date.action = 'add'" .
            (('' !== $collfilter['sql']) ?  " AND (" . $collfilter['sql'] . ")" : '')
            . ") AS tt
            GROUP BY tt.usrid
            ORDER BY nb ASC ";
*/
        $sql = ""
            . " SELECT tt.usrid, tt.user, sum( 1 ) AS nb\n"
            . " FROM (\n"
            . "     SELECT DISTINCT(log.id), log.usrid, log.user\n"
            . "     FROM (log_docs)\n"
            . "     INNER JOIN log FORCE INDEX (date_site) ON (log_docs.log_id = log.id)\n"
            . "     WHERE " . $datefilter['sql'] . " AND log_docs.action = 'add'"
            . ") AS tt\n"
            . " GROUP BY tt.usrid\n"
            . " ORDER BY nb ASC ";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

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
