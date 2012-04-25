<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_question extends module_report
{
    protected $cor_query = array(
        'user'        => 'log.user'
        , 'usrid'       => 'log.usrid'
        , 'ddate'       => 'log_search.date'
        , 'date'        => 'log_search.date'
        , 'societe'     => 'log.societe'
        , 'pays'        => 'log.pays'
        , 'activite'    => 'log.activite'
        , 'fonction'    => 'log.fonction'
        , 'site'        => 'log.site'
        , 'sit_session' => 'log.sit_session'
        , 'coll_list'   => 'log.coll_list'
        , 'appli'       => 'log.appli'
        , 'ip'          => 'log.ip'
    );

    /**
     * constructor
     *
     * @name download::__construct()
     * @param $arg1 start date of the  report
     * @param $arg2 end date of the report
     * @param $sbas_id id of the databox
     */
    public function __construct($arg1, $arg2, $sbas_id, $collist)
    {
        parent::__construct($arg1, $arg2, $sbas_id, $collist);
        $this->title = _('report:: question');
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

    public function colFilter($field)
    {
        $ret = array();
        $s = $this->sqlBuilder('question');
        $var = $s->sqlDistinctValByField($field);
        $sql = $var['sql'];
        $params = $var['params'];
        $stmt = $s->getConnBas()->prepare($sql);
        $stmt->execute($params);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $value = $row['val'];
            if ($field == 'appli')
                $caption = implode(' ', phrasea::modulesName(@unserialize($row['val'])));
            elseif ($field == "ddate")
                $caption = phraseadate::getPrettyString(new DateTime($value));
            else
                $caption = $row['val'];
            $ret[] = array('val'   => $caption, 'value' => $value);
        }

        return $ret;
    }

    protected function buildResult($rs)
    {
        $tab = array();
        $i = 0;
        foreach ($rs as $row) {
            if ($this->enable_limit && ($i > $this->nb_record))
                break;
            foreach ($this->champ as $key => $value) {
                if ($row[$value]) {
                    if ($value == 'ddate')
                        $this->result[$i][$value] =
                            $this->pretty_string ? phraseadate::getPrettyString(new DateTime($row[$value])) : $row[$value];
                    else
                        $this->result[$i][$value] = $row[$value];
                }
                else
                    $this->result[$i][$value] = "<i>" . _('report:: non-renseigne') . "</i>";
            }
            $i ++;
        }
    }
}

