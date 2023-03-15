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

class module_report_nav extends module_report
{
    /**
     * @desc total of record on current report
     * @var string
     */
    public $total_pourcent = null;
    public $config = false;
    public $cor_query = [
        'user'      => 'log.user',
        'site'      => 'log.site',
        'societe'   => 'log.societe',
        'pays'      => 'log.pays',
        'activite'  => 'log.activite',
        'fonction'  => 'log.fonction',
        'usrid'     => 'log.usrid',
        'coll_id'   => 'record.coll_id',
        'ddate'     => "log.date",
        'id'        => 'log_docs.id',
        'log_id'    => 'log_docs.log_id',
        'record_id' => 'log_docs.record_id',
        'final'     => 'log_docs.final',
        'comment'   => 'log_docs.comment',
        'size'      => 'subdef.size'
    ];

    /**
     * constructor
     *
     * @param Application $app
     * @param string      $arg1    start date of the report
     * @param string      $arg2    end date of the report
     * @param integer     $sbas_id databox id
     * @param string      $collist
     */
    public function __construct(Application $app, $arg1, $arg2, $sbas_id, $collist)
    {
//        parent::__construct($app, $arg1, $arg2, $sbas_id, $collist);
        parent::__construct($app, $arg1, $arg2, $sbas_id, "");
    }

    private function setTotalPourcent()
    {
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();

        $report_filter = $filter->getReportFilter();
        $params = array_merge([], $report_filter['params']);

        $sql = '
            SELECT COUNT(log.id) AS total
                FROM log FORCE INDEX (date_site)
                WHERE ' . $report_filter['sql'] . ' AND nav != "" AND !ISNULL(usrid)';

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return (int) $row['total'];
    }

    /**
     * @desc empty $champ, $result, $display, $display_value
     * @return void
     */
    private function initialize()
    {
        $this->report['legend'] = [];
        $this->report['value'] = [];
        $this->result = [];
        $this->champ = [];
        $this->default_display = [];
        $this->display = [];
    }

    /**
     * @desc report the browser used by users
     * @param  array $tab config  for the html table
     * @return tab
     */
    public function buildTabNav($tab = false)
    {
        $i = 0;

        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();
        $this->title = $this->app->trans('report:: navigateur');

        $this->total_pourcent = $this->setTotalPourcent();

        if (is_null($this->total_pourcent)) {
            return $this->report;
        }

        $report_filter = $filter->getReportFilter();
        $params = array_merge([], $report_filter['params']);

        $sql = '
            SELECT nav, SUM(1) AS nb, ROUND((SUM(1) / ' . $this->total_pourcent . ' * 100), 1) AS pourcent
                FROM log FORCE INDEX (date_site, nav)
                WHERE ' . $report_filter['sql'] . ' AND nav != "" AND !ISNULL(usrid)
            GROUP BY nav
            ORDER BY nb DESC';

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $this->initialize();

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = ($value == 'pourcent') ? $row[$value] . '%' : $row[$value];
            }
            $this->report['value'][] = $row['nb'];
            $this->report['legend'][] = $row['nav'];
            $i ++;
        }

        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc report the OS from user
     * @param  array $tab config for the html table
     * @return array
     */
    public function buildTabOs($tab = false)
    {
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();
        $i = 0;
        $this->title = $this->app->trans('report:: Plateforme');

        $this->total_pourcent = $this->setTotalPourcent();

        if (is_null($this->total_pourcent)) {
            return $this->report;
        }

        $report_filter = $filter->getReportFilter();
        $params = array_merge([], $report_filter['params']);

        $sql = '
            SELECT os, COUNT(os) AS nb, ROUND((COUNT(os)/' . $this->total_pourcent . '*100),1) AS pourcent

                FROM log FORCE INDEX (date_site, os)
                WHERE '. $report_filter['sql'] . ' AND os != "" AND !ISNULL(usrid)

            GROUP BY os
            ORDER BY nb DESC';

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $this->initialize();

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = ($value == 'pourcent') ? $row[$value] . '%' : $row[$value];
            }
            $i ++;
            $this->report['value'][] = $row['nb'];
            $this->report['legend'][] = $row['os'];
        }
        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc report the resolution that are using the users
     * @param  array $tab config for the html table
     * @return array
     */
    public function buildTabRes($tab = false)
    {
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();
        $this->title = $this->app->trans('report:: resolution');
        $i = 0;

        $this->total_pourcent = $this->setTotalPourcent();

        if (is_null($this->total_pourcent)) {
            return($this->report);
        }

        $report_filter = $filter->getReportFilter();
        $params = array_merge([], $report_filter['params']);

        $sql = '
            SELECT res, COUNT(res) AS nb, ROUND((COUNT(res)/ ' . $this->total_pourcent . '*100),1) AS pourcent

                FROM log FORCE INDEX (date_site, res)
                WHERE '. $report_filter['sql'] . ' AND res != "" AND !ISNULL(usrid)

            GROUP BY res
            ORDER BY nb DESC
            LIMIT 0, 10';

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $this->initialize();

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = ($value == 'pourcent') ?
                    $row[$value] . '%' : $row[$value];
            }
            $i ++;
            $this->report['value'][] = $row['nb'];
            $this->report['legend'][] = $row['res'];
        }

        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc report the combination (OS - Navigateur) that are using the users
     * @param array $tab config for the html table
     */
    public function buildTabCombo($tab = false)
    {
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();
        $this->title = $this->app->trans('report:: navigateurs et plateforme');
        $i = 0;

        $this->total_pourcent = $this->setTotalPourcent();

        if (is_null($this->total_pourcent)) {
            return($this->report);
        }

        $report_filter = $filter->getReportFilter();
        $params = array_merge([], $report_filter['params']);

        $sql = "
            SELECT tt.combo, COUNT( tt.combo ) AS nb, ROUND((COUNT(tt.combo)/" . $this->total_pourcent . "*100), 1) AS pourcent
            FROM (
                SELECT CONCAT( nav, '-', os ) AS combo
                FROM log FORCE INDEX (date_site, os_nav)
                WHERE ". $report_filter['sql'] ."  AND nav != '' AND os != '' AND !ISNULL(usrid)
            ) AS tt
            GROUP BY tt.combo
            ORDER BY nb DESC
            LIMIT 0 , 10";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $this->initialize();

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                $this->result[$i][$value] = ($value == 'pourcent') ?
                    $row[$value] . '%' : $row[$value];
            }
            $i ++;
            $this->report['value'][] = $row['nb'];
            $this->report['legend'][] = $row['combo'];
        }
        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    /**
     * @desc report the most consulted module by the users
     * @param  array $tab
     * @return array
     */
    public function buildTabModule($tab = false)
    {
        $this->initialize();
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();
        $this->title = $this->app->trans('report:: modules');
        $x = [];
        $tab_appli = [];

        $this->total_pourcent = $this->setTotalPourcent();

        if (is_null($this->total_pourcent)) {
            return($this->report);
        }

        $report_filter = $filter->getReportFilter();
        $params = array_merge([], $report_filter['params']);

        $sql = '
            SELECT appli
                FROM log FORCE INDEX (date_site)
                WHERE ' . $report_filter['sql'] . ' AND appli != \'a:0:{}\' AND !ISNULL(usrid)
            GROUP BY appli';

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $this->initialize();

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $applis = false;
            if (($applis = @unserialize($row['appli'])) !== false)
                array_push($x, phrasea::modulesName($this->app['translator'], $applis));
            else
                array_push($x, 'NULL');
        }
        foreach ($x as $key => $tab_value) {
            if (is_array($tab_value)) {
                foreach ($tab_value as $key2 => $value) {
                    if ( ! isset($tab_appli[$value]))
                        $tab_appli[$value] = 0;
                    $tab_appli[$value] ++;
                }
            }
        }
        $total = array_sum($tab_appli);

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($tab_appli as $appli => $nb) {
            $pourcent = round(($nb / $total) * 100, 1);
            foreach ($this->champ as $key => $value) {
                $this->result[] = [
                    'appli'    => $appli,
                    'nb'       => $nb,
                    'pourcent' => $pourcent . '%'
                ];
            }
            $this->report['value'][] = $nb;
            $this->report['legend'][] = $appli;
        }
        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    public function buildTabGrpInfo($req, array $params, $val, $tab = false, $on = false)
    {
        $this->initialize();
        empty($on) ? $on = false : "";
        $filter_id_apbox = $filter_id_datbox = [];
        $conn = $this->app->getApplicationBox()->get_connection();

        $this->title = $this->app->trans('report:: Information sur les utilisateurs correspondant a %critere%', ['%critere%' => $val]);

        if ($on) {
            if ( ! empty($req)) {
                $stmt = $this->app->findDataboxById($this->sbas_id)->get_connection()->prepare($req);
                $stmt->execute($params);
                $rsu = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rsu as $row_user) {
                    $filter_id_apbox[] = "usr_id = " . (int) $row_user['usrid'];
                    $filter_id_datbox[] = "log.usrid = " . (int) $row_user['usrid'];
                }
                $filter_id_apbox = implode(' OR ', $filter_id_apbox);
                $filter_id_datbox = implode(' OR ', $filter_id_datbox);
            }

            $sql = "
                SELECT
                    login as identifiant,
                    last_name as nom,
                    email as mail,
                    address AS adresse,
                    phone AS tel
                FROM Users
                WHERE $on = :value " . (('' !== $filter_id_apbox) ? "AND (" . $filter_id_apbox . ")" : '');
        } else {
            $sql = '
                SELECT
                    login AS identifiant,
                    last_name AS nom,
                    email AS mail,
                    address AS adresse,
                    phone AS tel
                 FROM Users
                 WHERE (id = :value)';
        }

        $params2 = [':value' => $val];
        $stmt = $conn->prepare($sql);
        $stmt->execute($params2);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($row as $fieldname => $value)
                $row[$fieldname] = $value ? $value : $this->app->trans('report:: non-renseigne');
            $this->result[] = $row;
        }
        if ($on == false) {
            $login = empty($this->result[0]['identifiant']) ?
                $this->app->trans('phraseanet::utilisateur inconnu') :
                $this->result[0]['identifiant'];

            $this->title = $this->app->trans('report:: Information sur l\'utilisateur %name%', ['%name%' => $login]);
        }
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    /**
     * Return basic information about a record
     *
     * @param integer $bid base id
     * @param integer $rid record id
     * @param array   $tab config for the html table
     *
     * @return array
     */
    public function buildTabUserWhat($bid, $rid, $tab = false)
    {
        $this->initialize();
        $sbas_id = phrasea::sbasFromBas($this->app, $bid);

        try {
            $record = new record_adapter($this->app, $sbas_id, $rid);
        } catch (\Exception_Record_AdapterNotFound $e) {
            return $this->report;
        }

        $this->setDisplay($tab);
        $this->champ = [
            'photo',
            'record_id',
            'date',
            'type',
            'titre',
            'taille'
        ];

        $document = $record->get_subdef('document');
        $this->title = $this->app->trans('report:: Information sur l\'enregistrement numero %number%', ['%number%' => (int) $rid]);

        $x = $record->get_thumbnail();
        $this->result[] = [
            'photo'     =>
            "<img style='width:" . $x->get_width() . "px;height:" . $x->get_height() . "px;'
                        src='" . (string) $x->get_url() . "'>"
            , 'record_id' => $record->getRecordId()
            , 'date'      => $this->app['date-formatter']->getPrettyString($document->get_creation_date())
            , 'type'      => $document->get_mime()
            , 'titre'     => $record->get_title(['encode'=> record_adapter::ENCODE_FOR_HTML])
            , 'taille'    => $document->get_size()
        ];

        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    public function buildTabInfoNav($tab = false, $navigator)
    {
        $databox = $this->app->findDataboxById($this->sbas_id);
        $conn = $databox->get_connection();
        $this->title = $this->app->trans('report:: Information sur le navigateur %name%', ['%name%' => $navigator]);
        $sqlBuilder = new module_report_sql($this->app, $this);
        $filter = $sqlBuilder->getFilters();
        $report_filter = $filter->getReportFilter();
        $params = array_merge($report_filter['params'], [':browser' => $navigator]);

        $sql = "
            SELECT DISTINCT(tt.version), COUNT(tt.version) as nb
            FROM (
                SELECT DISTINCT (log.id), version
                FROM log FORCE INDEX (date_site, nav, version)
                WHERE nav = :browser
                AND ". $report_filter['sql'] . "
            ) AS tt
            GROUP BY version
            ORDER BY nb DESC";

// no_file_put_contents("/tmp/report.txt", sprintf("%s (%s)\n%s\n\n", __FILE__, __LINE__, $sql), FILE_APPEND);

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        $this->result = $rs;
        $this->total = sizeof($this->result);
        $this->calculatePages();
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }
}
