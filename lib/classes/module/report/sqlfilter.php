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
        $this->conn = $app->findDataboxById($report->getSbasId())->get_connection();

        if (is_array($report->getTransQueryString()))
            $this->cor_query = $report->getTransQueryString();

        $this->buildFilter($report);

        $this->report = $report;
    }

    public static function constructDateFilter($dmin, $dmax, $dateField = 'log_date.date')
    {
        return array(
            'sql' => ($dmin && $dmax ? ' '.$dateField.' > :date_min AND '.$dateField.' < :date_max ' : false)
        , 'params' => ($dmin && $dmax ? array(':date_min' => $dmin, ':date_max' => $dmax) : array())
        );
    }

    public function getCorFilter()
    {
        return $this->cor_query;
    }

    public function getReportFilter()
    {
        $sql = '';

        $params = [':log_site' => $this->app['conf']->get(['main', 'key'])];

        if ($this->filter['date'] && $this->filter['date']['sql'] !== '') {
            $sql .= $this->filter['date']['sql'] . ' AND ';
            $params = array_merge($params, $this->filter['date']['params']);
        }
        if ($this->filter['user'] && $this->filter['user']['sql'] !== '') {
            $sql .= $this->filter['user']['sql'] . ' AND ';
            $params = array_merge($params, $this->filter['user']['params']);
        }

        $sql .= ' log.site = :log_site';

        return array('sql' => $sql, 'params' => $params);
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
        return array('sql' => "log.usrid = :usr_id_filter", 'params' => array(':usr_id_filter' => $id));
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
