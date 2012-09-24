<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
class module_report_download extends module_report
{
    protected $cor_query = array(
        'user'      => 'log.user',
        'site'      => 'log.site',
        'societe'   => 'log.societe',
        'pays'      => 'log.pays',
        'activite'  => 'log.activite',
        'fonction'  => 'log.fonction',
        'usrid'     => 'log.usrid',
        'coll_id'   => 'record.coll_id',
        'ddate'     => "log_docs.date",
        'id'        => 'log_docs.id',
        'log_id'    => 'log_docs.log_id',
        'record_id' => 'log_docs.record_id',
        'final'     => 'log_docs.final',
        'comment'   => 'log_docs.comment',
        'size'      => 'subdef.size',
        'mime'      => 'subdef.mime',
        'file'      => 'subdef.file'
    );

    /**
     * constructor
     *
     * @name download::__construct()
     * @param $arg1 start date of the  report
     * @param $arg2 end date of the report
     * @param $sbas_id id of the databox
     */
    public function __construct(Application $app, $arg1, $arg2, $sbas_id, $collist)
    {
        parent::__construct($app, $arg1, $arg2, $sbas_id, $collist);
        $this->title = _('report:: telechargements');
    }

    /**
     * @desc build the specified requete
     * @param $obj $conn the current connection to databox
     * @return string
     */
    protected function buildReq($groupby = false, $on = false)
    {
        $sql = $this->sqlBuilder('download')
                ->setOn($on)->setGroupBy($groupby)->buildSql();

        $this->req = $sql->getSql();
        $this->params = $sql->getParams();
        $this->total = $sql->getTotalRows();
    }

    public function colFilter($field, $on = false)
    {
        $ret = array();
        $s = $this->sqlBuilder('download');
        $var = $s->sqlDistinctValByField($field);
        $sql = $var['sql'];
        $params = $var['params'];

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $value = $row['val'];
            if ($field == 'coll_id') {
                $caption = phrasea::bas_names(phrasea::baseFromColl($this->sbas_id, $value, $this->app), $this->app);
            } elseif ($field == 'ddate')
                $caption = $this->app['date-formatter']->getPrettyString(new DateTime($value));
            elseif ($field == 'size')
                $caption = p4string::format_octets($value);
            else
                $caption = $value;
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
        $pref = parent::getPreff($app, $this->sbas_id);

        foreach ($rs as $row) {
            if ($this->enable_limit && ($i > $this->nb_record))
                break;

            foreach ($this->champ as $column) {
                $this->formatResult($column, $row[$column], $i);
            }

            if (array_key_exists('record_id', $row)) {
                $record = new \record_adapter($app, $this->sbas_id, $row['record_id']);

                foreach ($pref as $field) {
                    try {
                        $this->result[$i][$field] = $record->get_caption()
                            ->get_field($field)
                            ->get_serialized_values();
                    } catch (\Exception $e) {
                        $this->result[$i][$field] = '';
                    }
                }
            }
            $i ++;
        }
    }

    private function formatResult($column, $value, $i)
    {
        if ($value) {
            if ($column == 'coll_id')
                $this->result[$i][$column] = $this->formatCollId($value);
            elseif ($column == 'ddate')
                $this->result[$i][$column] = $this->formatDateValue($value);
            elseif ($column == 'size')
                $this->result[$i][$column] = p4string::format_octets($value);
            else
                $this->result[$i][$column] = $value;
        } else {
            if ($column == 'comment')
                $this->result[$i][$column] = '';
            else
                $this->result[$i][$column] = $this->formatEmptyValue();
        }
    }

    private function formatEmptyValue()
    {
        return '<i>' . _('report:: non-renseigne') . '</i>';
    }

    private function formatDateValue($value)
    {
        $datetime = new DateTime($value);
        $dateString = $datetime->format(DATE_ATOM);

        return $this->pretty_string ?
            $this->app['date-formatter']->getPrettyString($datetime) : $dateString;
    }

    private function formatCollId($value)
    {
        return phrasea::bas_names(phrasea::baseFromColl($this->sbas_id, $value, $this->app), $this->app);
    }

    public static function getNbDl(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $conn = connection::getPDOConnection($app, $sbas_id);
        $registry = $app['phraseanet.registry'];

        $params = array(':site_id'  => $registry->get('GV_sit'));
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $finalfilter = $datefilter['sql'] . ' AND ';
        $finalfilter .= $collfilter['sql'] . ' AND ';
        $finalfilter .= 'log.site = :site_id';

        $sql = '
            SELECT SUM(1) AS nb
            FROM (  log
                INNER JOIN log_docs as log_date ON log.id = log_date.log_id
                INNER JOIN record on log_date.record_id = record.record_id
            )
            WHERE (
                ' . $finalfilter . '
            )
            AND (
                log_date.action = \'download\'
                OR log_date.action = \'mail\'
            )
            ORDER BY log_date.date DESC
        ';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $row ? $row['nb'] : 0;
    }

    public static function getTopDl(Application $app, $dmin, $dmax, $sbas_id, $list_coll_id)
    {
        $appbox = $app['phraseanet.appbox'];
        $databox = $appbox->get_databox((int) $sbas_id);
        $conn = $databox->get_connection();

        $registry = $databox->get_registry();

        $params = array(':site_id'  => $registry->get('GV_sit'));
        $datefilter = module_report_sqlfilter::constructDateFilter($dmin, $dmax);
        $collfilter = module_report_sqlfilter::constructCollectionFilter($app, $list_coll_id);
        $params = array_merge($params, $datefilter['params'], $collfilter['params']);

        $finalfilter = "";
        $array = array(
            'preview' => array(),
            'document' => array()
        );

        $finalfilter .= $datefilter['sql'] . ' AND ';
        $finalfilter .= $collfilter['sql'] . ' AND ';
        $finalfilter .= 'log.site = :site_id';

        $sql = '
            SELECT record.record_id as id, SUM(1) AS nb, subdef.name
            FROM ( log
                INNER JOIN log_docs as log_date  ON log.id = log_date.log_id
                INNER JOIN record    ON log_date.record_id = record.record_id
                INNER JOIN subdef    ON subdef.record_id = record.record_id
            )
            WHERE (
                    ' . $finalfilter . '
            )
            AND ( log_date.action = \'download\'
                OR log_date.action = \'mail\'
            )
            AND subdef.name = log_date.final
            GROUP BY id, name
            ORDER BY nb DESC
        ';

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $record = $databox->get_record($row['id']);

            $k = $row['id'] . '_' . $sbas_id;
            $orig_name = $record->get_original_name();

            if ($row['name'] == 'document') {
                $array[$row['name']][$k]['nb'] = (int) $row['nb'];
                $array[$row['name']][$k]['lib'] = $orig_name;
                $array[$row['name']][$k]['sbasid'] = $sbas_id;
                $array[$row['name']][$k]['id'] = $row['id'];
            } elseif ($row['name'] == "preview") {
                $array[$row['name']][$k]['nb'] = (int) $row['nb'];
                $array[$row['name']][$k]['lib'] = $orig_name;
                $array[$row['name']][$k]['sbasid'] = $sbas_id;
                $array[$row['name']][$k]['id'] = $row['id'];
            }
        }

        return $array;
    }
}

