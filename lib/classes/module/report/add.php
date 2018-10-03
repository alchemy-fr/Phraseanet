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

class module_report_add extends module_report
{
    protected $cor_query = [
        'user'      => 'log.user',
        'site'      => 'log.site',
        'societe'   => 'log.societe',
        'pays'      => 'log.pays',
        'activite'  => 'log.activite',
        'fonction'  => 'log.fonction',
        'usrid'     => 'log.usrid',
        'getter'    => 'd.final',
        'date'      => "DATE(d.date)",
        'id'        => 'd.id',
        'log_id'    => 'd.log_id',
        'record_id' => 'd.record_id',
        'final'     => 'd.final',
        'comment'   => 'd.comment',
        'size'      => 's.size'
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
        parent::__construct($app, $arg1, $arg2, $sbas_id, $collist);
        $this->title = $this->app->trans('report:: document ajoute');
    }

    /**
     * @desc build the specified requete
     * @param $obj $conn the current connection to databox
     * @return string
     */
    protected function buildReq($groupby = false, $on = false)
    {
        $sqlBuilder = $this->sqlBuilder('action')->setGroupBy($groupby)->setOn($on)
                ->setAction('add')->buildSql();
        $this->req = $sqlBuilder->getSql();
        $this->params = $sqlBuilder->getParams();
        $this->total = $sqlBuilder->getTotalRows();
    }

    public function colFilter($field, $on = false)
    {
        $sqlBuilder = $this->sqlBuilder('action')->setAction('add');
        $var = $sqlBuilder->sqlDistinctValByField($field);
        $sql = $var['sql'];
        $params = $var['params'];
        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = [];

        foreach ($rs as $row) {
            $value = $row['val'];
            $caption = $value;
            if ($field == "getter") {
                if (null !== $user = $this->app['repo.users']->find($value)) {
                    $caption = $user->getDisplayName();
                }
            } elseif ($field == 'date')
                $caption = $this->app['date-formatter']->getPrettyString(new DateTime($value));
            elseif ($field == 'size')
                $caption = p4string::format_octets($value);

            $ret[] = ['val'   => $caption, 'value' => $value];
        }

        return $ret;
    }

    protected function buildResult(Application $app, $rs)
    {
        $i = 0;
        foreach ($rs as $row) {
            foreach ($this->champ as $key => $value) {
                if ($row[$value]) {
                    if ($value == 'date') {
                        $this->result[$i][$value] = $this->pretty_string ? $this->app['date-formatter']->getPrettyString(new DateTime($row[$value])) : $row[$value];
                    } elseif ($value == 'size') {
                        $this->result[$i][$value] = p4string::format_octets($row[$value]);
                    } else
                        $this->result[$i][$value] = $row[$value];
                } else {
                    if ($value == 'comment') {
                        $this->result[$i][$value] = '&nbsp;';
                    } else {
                        $this->result[$i][$value] = '<i>' . $this->app->trans('report:: non-renseigne') . '</i>';
                    }
                }
            }
            $i ++;
            if ($i >= $this->nb_record)
                break;
        }
    }
}
