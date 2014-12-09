<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class module_report_sqlfilter
{
    public $conn;
    private $filter;
    private $cor_query = [];
    private $app;
    private $report;

    public function __construct(Application $app, module_report $report)
    {
        $this->app = $app;
        $this->conn = $app['phraseanet.appbox']->get_databox($report->getSbasId())->get_connection();

        if (is_array($report->getTransQueryString()))
            $this->cor_query = $report->getTransQueryString();

        $this->buildFilter($report);

        $this->report = $report;
    }

    public static function constructDateFilter($dmin, $dmax, $dateField = 'log_date.date')
    {
<<<<<<< HEAD
        return [
            'sql' => ($dmin && $dmax ? ' log_date.date > :date_min AND log_date.date < :date_max ' : false)
            , 'params' => ($dmin && $dmax ? [':date_min' => $dmin, ':date_max' => $dmax] : [])
        ];
    }

    public static function constructCollectionFilter(Application $app, $list_coll_id)
    {
        $ret = ['sql'    => '', 'params' => []];
        $coll_filter = [];
        foreach (array_filter(explode(',', $list_coll_id)) as $val) {
            $val = \phrasea::collFromBas($app, $val);
            if ($val) {
                $coll_filter[] =  'log_colls.coll_id = ' . $val;
            }
        }
        $collections = array_unique($coll_filter);

        if (count($collections) > 0) {
            $ret['sql'] = ' (' . implode(' OR ', $collections) . ') ';
        }

        return $ret;
    }

=======
        return array(
            'sql' => ($dmin && $dmax ? ' '.$dateField.' > :date_min AND '.$dateField.' < :date_max ' : false)
        , 'params' => ($dmin && $dmax ? array(':date_min' => $dmin, ':date_max' => $dmax) : array())
        );
    }

>>>>>>> 3.8
    public function getCorFilter()
    {
        return $this->cor_query;
    }

    public function getReportFilter()
    {
        $sql = '';

<<<<<<< HEAD
        $params = [':log_site' => $this->app['conf']->get(['main', 'key'])];

=======
        $params = array(':log_site' => $this->app['phraseanet.configuration']['main']['key']);
>>>>>>> 3.8
        if ($this->filter['date'] && $this->filter['date']['sql'] !== '') {
            $sql .= $this->filter['date']['sql'] . ' AND ';
            $params = array_merge($params, $this->filter['date']['params']);
        }
        if ($this->filter['user'] && $this->filter['user']['sql'] !== '') {
            $sql .= $this->filter['user']['sql'] . ' AND ';
            $params = array_merge($params, $this->filter['user']['params']);
        }

<<<<<<< HEAD
        return ['sql' => $finalfilter, 'params' => $params];
=======
        $sql .= ' log.site = :log_site';

        return array('sql' => $sql, 'params' => $params);
>>>>>>> 3.8
    }

    public function getGvSitFilter()
    {
        $params = [];

        $sql = 'log.site = :log_site_gv_filter';
        $params[':log_site_gv_filter'] = $this->app['conf']->get(['main', 'key']);

        return ['sql' => $sql, 'params' => $params];
    }

    public function getUserIdFilter($id)
    {
<<<<<<< HEAD
        return ['sql' => "log.usrid = :usr_id_filter", 'params' => [':usr_id_filter' => $id]];
=======
        return array('sql' => "log.usrid = :usr_id_filter", 'params' => array(':usr_id_filter' => $id));
//        return array('sql' => "log.usrid=" . (int)$id, 'params' => array());
>>>>>>> 3.8
    }

    public function getDateFilter()
    {
        return $this->filter['date'];
    }

    public function getUserFilter()
    {
        return $this->filter['user'];
    }

    public function getRecordFilter()
    {
        return $this->filter['record'];
    }

    public function getLimitFilter()
    {
        return $this->filter['limit'];
    }

    public function getOrderFilter($customFieldMap = null)
    {
        if (null === $customFieldMap) {
            return $this->filter['order'];
        }

        return $this->overrideOrderFilter($customFieldMap);
    }

    private function dateFilter(module_report $report)
    {
        $this->filter['date'] = false;
/*
        if ($report->getDmin() && $report->getDmax()) {
            $this->filter['date'] = [
                'sql'    => ' (log.date > :date_min_f AND log.date < :date_max_f) '
<<<<<<< HEAD
                , 'params' => [
                    ':date_min_f' => $report->getDmin()
                    , ':date_max_f' => $report->getDmax()
                ]
            ];
=======
            , 'params' => array(
                    ':date_min_f' => $report->getDmin()
                , ':date_max_f' => $report->getDmax()
                )
            );
>>>>>>> 3.8
        }
*/
        $sql = "";
        if($report->getDmin()) {
            $sql = $report->getDateField().">=" . $this->conn->quote($report->getDmin());
        }
        if($report->getDmax()) {
            if($sql != "") {
                $sql .= " AND ";
            }
            $sql .= $report->getDateField()."<=" . $this->conn->quote($report->getDmax());
        }
        $this->filter['date'] = array(
            'sql' => $sql, 'params' => array()
        );

        return;
    }

    private function userFilter(module_report $report)
    {
        $this->filter['user'] = false;
        $f = $report->getTabFilter();

        if (sizeof($f) > 0) {
            $filter = [];
            $params = [];
            $n = 0;
            foreach ($f as $field => $value) {
                if (array_key_exists($value['f'], $this->cor_query))
                    $value['f'] = $this->cor_query[$value['f']];

                if ($value['o'] == 'LIKE') {
                    $filter[] = $value['f'] . ' ' . $value['o'] . ' \'%' . str_replace(["'", '%'], ["\'", '\%'], ' :user_filter' . $n) . '%\'';
                    $params[':user_filter' . $n] = $value['v'];
                } elseif ($value['o'] == 'OR') {
                    $filter[] = $value['f'] . ' ' . $value['o'] . ' :user_filter' . $n;
                    $params[':user_filter' . $n] = $value['v'];
                } else {
                    $filter[] = $value['f'] . ' ' . $value['o'] . ' :user_filter' . $n;
                    $params[':user_filter' . $n] = $value['v'];
                }

                $n ++;
            }

            if (count($filter) > 0) {
                $filter_user = array('sql' => implode(' AND ', $filter), 'params' => $params);
                $this->filter['user'] = $filter_user;
            }

        }

        return;
    }

<<<<<<< HEAD
    private function collectionFilter(module_report $report)
    {
        $this->filter['collection'] = false;
        $coll_filter = [];

        if ($report->getUserId() == '') {
            return;
        }

        $tab = array_filter(explode(",", $report->getListCollId()));

        if (count($tab) > 0) {
            foreach ($tab as $val) {
                $val = \phrasea::collFromBas($this->app, $val);
                if (!!$val) {
                    $coll_filter[] =  'log_colls.coll_id = ' . $val;
                }
            }

            $collections = array_unique($coll_filter);

            if (count($collections) > 0) {
                $this->filter['collection'] = array('sql' => ' (' . implode(' OR ', array_unique($coll_filter)) . ') ', 'params' => array());
            }
        }

        return;
    }

=======
>>>>>>> 3.8
    private function recordFilter(module_report $report)
    {
        $this->filter['record'] = false;
        $dl_coll_filter = $params = [];
        $n = 0;
        if (($report->getUserId() != '')) {
            $tab = explode(",", $report->getListCollId());
            foreach ($tab as $val) {
                $dl_coll_filter[] = "record.coll_id = :record_fil" . $n;
                $params[":record_fil" . $n] = phrasea::collFromBas($this->app, $val);
                $n ++;
            }
            if (count($dl_coll_filter) > 0) {
                $this->filter['record'] = array('sql' => implode(' OR ', $dl_coll_filter), 'params' => $params);
            }
        }

        return;
    }

    private function orderFilter(module_report $report)
    {
        $this->filter['order'] = false;
        if (sizeof($report->getOrder()) > 0) {
            $this->filter['order'] = " ORDER BY "
                . $this->cor_query[$report->getOrder('champ')]
                . ' ' . $report->getOrder('order');
        }

        return;
    }

    private function limitFilter(module_report $report)
    {
        $p = $report->getNbPage();
        $r = $report->getNbRecord();

        $this->filter['limit'] = false;
        if ($p && $r) {
            $limit_inf = (int) ($p - 1) * $r;
            $limit_sup = (int) $r;
            $this->filter['limit'] = " LIMIT " . $limit_inf . ', ' . $limit_sup;
        }

        return;
    }

    private function buildFilter(module_report $report)
    {
        $this->dateFilter($report);
        $this->limitFilter($report);
        $this->orderFilter($report);
        $this->recordFilter($report);
        $this->userFilter($report);

        return;
    }

    private function overrideOrderFilter($customFieldMap)
    {
        if (sizeof($this->report->getOrder()) > 0) {
            if (!isset($customFieldMap[$this->cor_query[$this->report->getOrder('champ')]])) {
                return false;
            }

            return " ORDER BY "
                . $customFieldMap[$this->cor_query[$this->report->getOrder('champ')]]
                . ' ' . $this->report->getOrder('order');
        }

        return false;
    }
}
