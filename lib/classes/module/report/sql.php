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

class module_report_sql
{
    /**
     *
     * @var Connection
     */
    public $conn;

    /**
     *
     * @var Connection
     */
    public $connbas;

    /**
     *
     * @var module_report_sqlfilter
     */
    public $filter;
    public $sql;
    public $params;
    public $total_row;
    public $enable_limit;
    public $groupby = false;
    public $on = false;

    public function __construct(Application $app, module_report $report)
    {
        $this->conn = $app->getApplicationBox()->get_connection();
        $this->connbas = $app->findDataboxById($report->getSbasId())->get_connection();
        $this->filter = new module_report_sqlfilter($app, $report);
        $this->sql = '';
        $this->params = [];
        $this->total_row = 0;
        $this->enable_limit = $report->getEnableLimit();
    }

    public function setGroupBy($groupby)
    {
        $this->groupby = $groupby;

        return $this;
    }

    public function getGroupby()
    {
        return $this->groupby;
    }

    public function setOn($on)
    {
        $this->on = $on;

        return $this;
    }

    public function getOn()
    {
        return $this->on;
    }

    public function setFilter(module_report_sqlfilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     *
     * @return module_report_sqlfilter
     */
    public function getFilters()
    {
        return $this->filter;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getTotalRows()
    {
        return $this->total_row;
    }

    public function setTotalrows($total)
    {
        $this->total_row = $total;
    }

    public function getTransQuery($champ)
    {
        $tanslation = $this->filter->getCorFilter();
        if (array_key_exists($champ, $tanslation)) {
            return $tanslation[$champ];
        } else {
            return $champ;
        }
    }

    /**
     *
     * @return Connection
     */
    public function getConnBas()
    {
        return $this->connbas;
    }
}
