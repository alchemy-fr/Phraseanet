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

class module_report_question extends module_report
{
    protected $cor_query = [
        'user'        => 'log.user',
        'usrid'       => 'log.usrid',
        'ddate'       => 'log_search.date',
        'date'        => 'log_search.date',
        'societe'     => 'log.societe',
        'pays'        => 'log.pays',
        'activite'    => 'log.activite',
        'fonction'    => 'log.fonction',
        'site'        => 'log.site',
        'sit_session' => 'log.sit_session',
        'appli'       => 'log.appli',
        'ip'          => 'log.ip'
    ];

    /**
     * constructor
     *
     * @param Application $app
     * @param string      $arg1    start date of the  report
     * @param string      $arg2    end date of the report
     * @param integer     $sbas_id id of the databox
     * @param string      $collist
     */
    public function __construct(Application $app, $arg1, $arg2, $sbas_id, $collist)
    {
        parent::__construct($app, $arg1, $arg2, $sbas_id, '');
        $this->title = $this->app->trans('report:: question');
    }

    /**
     * @desc build the specified requete
     * @param $obj $conn the current connection to databox
     * @return string
     */
    protected function buildReq($groupby = false)
    {
        $sql = $this->sqlBuilder('question')->setGroupBy($groupby)->buildSql();
        $this->req = $sql->getSql();
        $this->params = $sql->getParams();
        $this->total = $sql->getTotalRows();
    }

    public function colFilter($field, $on = false)
    {
        $ret = [];
        $sqlBuilder = $this->sqlBuilder('question');
        $var = $sqlBuilder->sqlDistinctValByField($field);
        $sql = $var['sql'];
        $params = $var['params'];
        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $value = $row['val'];
            if ($field == 'appli')
                $caption = implode(' ', phrasea::modulesName($this->app['translator'], @unserialize($row['val'])));
            elseif ($field == "ddate")
                $caption = $this->app['date-formatter']->getPrettyString(new DateTime($value));
            else
                $caption = $row['val'];
            $ret[] = ['val'   => $caption, 'value' => $value];
        }

        return $ret;
    }

    protected function buildResult(Application $app, $rs)
    {
        $i = 0;
        foreach ($rs as $row) {
            if ($this->enable_limit && ($i > $this->nb_record))
                break;
            foreach ($this->champ as $key => $value) {
                if ($row[$value]) {
                    if ($value == 'ddate')
                        $this->result[$i][$value] =
                            $this->pretty_string ? $this->app['date-formatter']->getPrettyString(new DateTime($row[$value])) : $row[$value];
                    else
                        $this->result[$i][$value] = $row[$value];
                } else
                    $this->result[$i][$value] = "<i>" . $this->app->trans('report:: non-renseigne') . "</i>";
            }
            $i ++;
        }
    }
}
