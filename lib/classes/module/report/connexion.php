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

class module_report_connexion extends module_report
{
    protected $cor_query = array(
        'user'        => 'log.user',
        'usrid'       => 'log.usrid',
        'ddate'       => 'log.date',
        'societe'     => 'log.societe',
        'pays'        => 'log.pays',
        'activite'    => 'log.activite',
        'fonction'    => 'log.fonction',
        'site'        => 'log.site',
        'sit_session' => 'log.sit_session',
        'appli'       => 'log.appli',
        'ip'          => 'log.ip'
    );

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
        $this->title = _('report::Connexions');
    }

    /**
     * @desc build the specified requete
     * @param $obj $conn the current connection to databox
     * @return string
     */
    protected function buildReq($groupby = false)
    {
        $sql = $this->sqlBuilder('connexion');
        $sql = $sql->setGroupBy($groupby);
        $sql = $sql->buildSql();
        $this->req = $sql->getSql();
        $this->params = $sql->getParams();
        $this->total = $sql->getTotalRows();
    }

    /**
     * @desc build the list with all distinct result
     * @param  string $field the field from the request displayed in a array
     * @return string $liste
     */
    public function colFilter($field)
    {
        $ret = array();
        $sqlBuilder = $this->sqlBuilder('connexion');
        $var = $sqlBuilder->sqlDistinctValByField($field);
        $sql = $var['sql'];
        $params = $var['params'];

        $stmt = $sqlBuilder->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $value = $row['val'];
            if ($field == "appli")
                $caption = implode(' ', phrasea::modulesName(@unserialize($value)));
            elseif ($field == 'ddate')
                $caption = $this->app['date-formatter']->getPrettyString(new DateTime($value));
            else
                $caption = $row['val'];
            $ret[] = array('val'   => $caption, 'value' => $value);
        }

        return $ret;
    }

    /**
     * @desc build the result from the specified sql
     * @param array  $champ all the field from the request displayed in a array
     * @param string $sql   the request from buildreq
     * @return $this->result
     */
    protected function buildResult(Application $app, $rs)
    {
        $i = 0;

        foreach ($rs as $row) {
            if ($this->enable_limit && ($i > $this->nb_record)) {
                break;
            }

            foreach ($this->champ as $key => $value) {
                if ( ! isset($row[$value])) {
                    $this->result[$i][$value] = '<i>' . _('report:: non-renseigne') . '</i>';
                    continue;
                }

                if ($value == 'appli') {
                    $applis = false;
                    if (($applis = @unserialize($row[$value])) !== false) {
                        if (empty($applis)) {
                            $this->result[$i][$value] = '<i>' . _('report:: non-renseigne') . '</i>';
                        } else {
                            $this->result[$i][$value] = implode(' ', phrasea::modulesName($applis));
                        }
                    } else {
                        $this->result[$i][$value] = '<i>' . _('report:: non-renseigne') . '</i>';
                    }
                } elseif ($value == 'ddate') {
                    $this->result[$i][$value] = $this->pretty_string ?
                        $this->app['date-formatter']->getPrettyString(new DateTime($row[$value])) :
                        $row[$value];
                } else {
                    $this->result[$i][$value] = $row[$value];
                }
            }
            $i ++;
        }
    }

    public static function getNbConn(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);

        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);

        $params = array_merge(array(
                ':site_id' => $app['phraseanet.registry']->get('GV_sit')
            ),
            $datefilter['params'],
            $collfilter['params']
        );

        $finalfilter = $datefilter['sql'] . ' AND ';
        $finalfilter .= $collfilter['sql'] . ' AND ';
        $finalfilter .= 'log_date.site = :site_id';

        $sql = "SELECT COUNT(DISTINCT(log_date.id)) as nb
                FROM log as log_date FORCE INDEX (date_site)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log_date.id = log_colls.log_id)
                WHERE " . $finalfilter;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return (int) $row['nb'];
    }
}
