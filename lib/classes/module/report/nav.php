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

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_nav extends module_report
{
    /**
     * @desc total of record on current report
     * @var string
     */
    public $total_pourcent = null;
    public $config = false;
    public $cor_query = array(
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
    );

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
        parent::__construct($app, $arg1, $arg2, $sbas_id, $collist);
        $this->total_pourcent = $this->setTotalPourcent();
    }

    private function setTotalPourcent()
    {
        $x = $this->getTransQueryString();

        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters();

        $params = array();
        $report_filter = $filter->getReportFilter();
        $coll_filter = $filter->getCollectionFilter();
        $site_filter = $filter->getGvSitFilter();
        $params = array_merge($report_filter['params'], $coll_filter['params'], $site_filter['params']);

        $sql = '
            SELECT
                SUM(1) AS total
            FROM log
            WHERE (' . $report_filter['sql'] . '
                AND nav != TRIM(\'\')
            )
            AND ' . $site_filter['sql'] . '
          AND (' . $coll_filter['sql'] . ')';

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $row['total'];
    }

    /**
     * @desc empty $champ, $result, $display, $display_value
     * @return void
     */
    private function initialize()
    {
        $this->report['legend'] = array();
        $this->report['value'] = array();
        $this->result = array();
        $this->champ = array();
        $this->default_display = array();
        $this->display = array();
    }

    /**
     * @desc return the filter to generate the good request
     * @param  object $conn the current connexion to appbox
     * @return string
     */
    private function getFilter()
    {
        return;
    }

    /**
     * @desc report the browser used by users
     * @param  array $tab config  for the html table
     * @return tab
     */
    public function buildTabNav($tab = false)
    {
        $i = 0;

        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters();
        $this->title = _('report:: navigateur');

        if (is_null($this->total_pourcent)) {
            return $this->report;
        }

        $params = array();
        $report_filter = $filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);

        $sql = '
            SELECT
                nav,
                COUNT(nav) AS nb,
                ROUND(
                    ( COUNT(nav) / ' . $this->total_pourcent . ' * 100),
                    1
                ) AS pourcent
            FROM log
            WHERE (' . $report_filter['sql'] . '
                AND nav != TRIM(\'\')
            )
            GROUP BY nav
            ORDER BY pourcent DESC';

        $this->initialize();

        $stmt = $s->getConnBas()->prepare($sql);
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
        $this->calculatePages($rs);
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
        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters();
        $i = 0;
        $this->title = _('report:: Plateforme');

        if (is_null($this->total_pourcent)) {
            return $this->report;
        }

        $params = array();
        $report_filter = $filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);

        $sql = '
            SELECT
                os,
                COUNT(os) AS nb,
                ROUND((COUNT(os)/' . $this->total_pourcent . '*100),1) AS pourcent
            FROM log
            WHERE ( ' . $report_filter['sql'] . '
                AND os != TRIM(\'\')
            )
            GROUP BY os
            ORDER BY pourcent DESC';

        $this->initialize();

        $stmt = $s->getConnBas()->prepare($sql);
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
        $this->calculatePages($rs);
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
        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters();
        $this->title = _('report:: resolution');
        $i = 0;
        if (is_null($this->total_pourcent)) {
            return($this->report);
        }

        $params = array();
        $report_filter = $filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);

        $sql = '
                SELECT
                    res,
                    COUNT(res) AS nb,
                    ROUND((COUNT(res)/ ' . $this->total_pourcent . '*100),1) AS pourcent
                FROM log
                WHERE (' . $report_filter['sql'] . '
                    AND res != TRIM(\'\')
                )
                GROUP BY res
                ORDER BY pourcent DESC
                LIMIT 0, 10';

        $this->initialize();

        $stmt = $s->getConnBas()->prepare($sql);
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
        $this->calculatePages($rs);
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
        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters();
        $this->title = _('report:: navigateurs et plateforme');
        $i = 0;
        if (is_null($this->total_pourcent)) {
            return($this->report);
        }

        $params = array();
        $report_filter = $filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);

        $sql = "
                SELECT
                    CONCAT( nav, '-', os ) AS combo,
                    COUNT( CONCAT( nav, '-', os ) ) AS nb,
                    ROUND(
                        (COUNT( CONCAT( nav ,'-', os ))/" . $this->total_pourcent . "*100),
                        1) AS pourcent
                FROM log
                WHERE (" . $report_filter['sql'] . "
                    AND nav != TRIM( '' )
                )
                AND os != TRIM( '' )
                GROUP BY combo
                ORDER BY nb DESC
                LIMIT 0 , 10";

        $this->initialize();

        $stmt = $s->getConnBas()->prepare($sql);
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
        $this->calculatePages($rs);
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
        $s = new module_report_sql($this->app, $this);
        $filter = $s->getFilters();
        $this->title = _('report:: modules');
        $x = array();
        $tab_appli = array();

        if (is_null($this->total_pourcent)) {
            return($this->report);
        }

        $params = array();
        $report_filter = $filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);

        $sql = '
            SELECT
                appli
            FROM log
            WHERE (' . $report_filter['sql'] . '
                AND appli != \'a:0:{}\'
            )
            GROUP BY appli
            ORDER BY appli DESC
        ';

        $this->initialize();

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $applis = false;
            if (($applis = @unserialize($row['appli'])) !== false)
                array_push($x, phrasea::modulesName($applis));
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
                $this->result[] = array(
                    'appli'    => $appli,
                    'nb'       => $nb,
                    'pourcent' => $pourcent . '%'
                );
            }
            $this->report['value'][] = $nb;
            $this->report['legend'][] = $appli;
        }
        $this->total = sizeof($this->result);
        $this->calculatePages($rs);
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    public function buildTabGrpInfo($req, array $params, $val, $tab = false, $on = false)
    {
        $this->initialize();
        empty($on) ? $on = false : "";
        $filter_id_apbox = $filter_id_datbox = array();
        $conn = connection::getPDOConnection($this->app);
        $conn2 = connection::getPDOConnection($this->app, $this->sbas_id);

        $datefilter = array();

        if ($this->dmin && $this->dmax) {
            $params = array(':dmin'     => $this->dmin, ':dmax'     => $this->dmax);
            $datefilter = "date > :dmin AND date < :dmax";
        }

        $this->title = sprintf(_('report:: Information sur les utilisateurs correspondant a %s'), $val);

        if ($on) {
            if ( ! empty($req)) {
                $stmt = $conn2->prepare($req);
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
                    usr_login as identifiant,
                    usr_nom as nom,
                    usr_mail as mail,
                    adresse, tel
                FROM usr
                WHERE $on = :value AND (" . $filter_id_apbox . ")";
        } else {
            $sql = '
                    SELECT
                        usr_login AS identifiant,
                        usr_nom    AS nom,
                        usr_mail  AS mail,
                        adresse,
                        tel
                     FROM usr
                     WHERE (usr_id = :value)';
        }

        $params2 = array(':value' => $val);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params2);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        foreach ($rs as $row) {
            foreach ($row as $fieldname => $value)
                $row[$fieldname] = $value ? $value : _('report:: non-renseigne');
            $this->result[] = $row;
        }
        if ($on == false) {
            $login = empty($this->result[0]['identifiant']) ?
                _('phraseanet::utilisateur inconnu') :
                $this->result[0]['identifiant'];

            $this->title = sprintf(
                _('report:: Information sur l\'utilisateur %s'), $login
            );
        }
        $this->calculatePages($rs);
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
        $record = new record_adapter($this->app, $sbas_id, $rid);

        $this->setDisplay($tab);
        $this->champ = array(
            'photo',
            'record_id',
            'date',
            'type',
            'titre',
            'taille'
        );

        $document = $record->get_subdef('document');
        $this->title = sprintf(
            _('report:: Information sur l\'enregistrement numero %d'), (int) $rid);

        $x = $record->get_thumbnail();
        $this->result[] = array(
            'photo'     =>
            "<img style='width:" . $x->get_width() . "px;height:" . $x->get_height() . "px;'
                        src='" . $x->get_url() . "'>"
            , 'record_id' => $record->get_record_id()
            , 'date'      => $this->app['date-formatter']->getPrettyString($document->get_creation_date())
            , 'type'      => $document->get_mime()
            , 'titre'     => $record->get_title()
            , 'taille'    => $document->get_size()
        );

        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }

    public function buildTabInfoNav($tab = false, $navigator)
    {
        $conn = connection::getPDOConnection($this->app, $this->sbas_id);
        $this->title = sprintf(
            _('report:: Information sur le navigateur %s'), $navigator);

        $params = array(':browser' => $navigator);

        $sql = "SELECT DISTINCT(version) as version, COUNT(version) as nb
            FROM log
            WHERE nav = :browser
            GROUP BY version
            ORDER BY nb DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->setChamp($rs);
        $this->setDisplay($tab);

        $this->result = $rs;
        $this->total = sizeof($this->result);
        $this->calculatePages($rs);
        $this->setDisplayNav();
        $this->setReport();

        return $this->report;
    }
}
