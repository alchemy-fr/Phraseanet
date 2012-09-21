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
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_edit extends module_report
{
    protected $cor_query = array(
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
        $this->title = _('report:: edited documents');
    }

    /**
     * @desc build the specified requete
     * @param $obj $conn the current connection to databox
     * @return string
     */
    protected function buildReq($groupby = false, $on = false)
    {
        $s = $this->sqlBuilder('action')->setGroupBy($groupby)->setOn($on)
                ->setAction('edit')->buildSql();
        $this->req = $s->getSql();
        $this->params = $s->getParams();
        $this->total = $s->getTotalRows();
    }

    public function colFilter($field, $on = false)
    {
        $s = $this->sqlBuilder('action')->setAction('edit');
        $var = $s->sqlDistinctValByField($field);
        $sql = $var['sql'];
        $params = $var['params'];

        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = array();
        foreach ($rs as $row) {
            $value = $row['val'];
            $caption = $value;
            if ($field == "getter") {
                try {
                    $user = User_Adapter::getInstance($value, $this->app);
                    $caption = $user->get_display_name();
                } catch (Exception $e) {

                }
            } elseif ($field == 'date') {
                $caption = $this->app['date-formatter']->getPrettyString(new DateTime($value));
            } elseif ($field == 'size') {
                $caption = p4string::format_octets($value);
            }

            $ret[] = array('val'   => $caption, 'value' => $value);
        }

        return $ret;
    }

    protected function buildResult(Application $app, $rs)
    {
        $i = 0;

        foreach ($rs as $row) {
            if ($i >= $this->nb_record)
                break;
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
                        $this->result[$i][$value] = '<i>' . _('report:: non-renseigne') . '</i>';
                    }
                }
            }
            $i ++;
        }
    }
}

