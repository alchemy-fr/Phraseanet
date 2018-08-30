<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;


class ReportConnections extends Report implements ReportInterface
{
    private $appKey;

    /* those vars will be set once by computeVars() */
    private $name = null;
    private $sql = null;
    private $columnTitles = [];
    private $keyName = null;


    public function getColumnTitles()
    {
        $this->computeVars();
        return $this->columnTitles;
    }

    public function getSql()
    {
        $this->computeVars();
        return $this->sql;
    }

    public function getKeyName()
    {
        $this->computeVars();
        return $this->keyName;
    }

    public function getName()
    {
        $this->computeVars();
        return $this->name;
    }

    public function getSqlParms()
    {
        return [
            ':site' => $this->appKey,
            ':dmin' => $this->parms['dmin'],
            ':dmax' => $this->parms['dmax']
        ];
    }

    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }


    private function computeVars()
    {
        if(!is_null($this->sql)) {
            // vars already computed
            return;
        }

        switch($this->parms['group']) {
            case null:
                $this->name = "Connections";
                $this->columnTitles = ['id', 'date', 'usrid', 'user', 'fonction', 'societe', 'activite', 'pays', 'nav', 'version', 'os', 'res', 'ip', 'user_agent'];
                $sql = "SELECT `id`, `date`, `usrid`, `user`, `fonction`, `societe`, `activite`, `pays`, `nav`, `version`, `os`, `res`, `ip`, `user_agent` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}";
                $this->keyName = null;
                break;
            case 'user':
                $this->name = "Connections per user";
                $this->columnTitles = ['user_id', 'user', 'min_date', 'max_date', 'nb'];
                $sql = "SELECT `usrid`, `user`, MIN(`date`) AS `dmin`, MAX(`date`) AS `dmax`, SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY `usrid`\n"
                    . " ORDER BY `nb` DESC"
                ;
                $this->keyName = 'usrid';
                break;
            case 'nav':
            case 'nav,version':
            case 'os':
            case 'os,nav':
            case 'os,nav,version':
            case 'res':
                $this->name = "Connections per " . $this->parms['group'];
                $groups  = explode(',', $this->parms['group']);
                $qgroups = implode(
                    ',',
                    array_map(function($g) {return '`'.$g.'`';}, $groups)
                );
                $this->columnTitles = $groups;
                $this->columnTitles[] = 'nb';
                $sql = "SELECT " . $qgroups . ", SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY " . $qgroups . "\n"
                    . " ORDER BY `nb` DESC"
                ;
                $this->keyName = null;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $this->sql = str_replace(
            '{{GlobalFilter}}',
            // "`site` =  :site AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            "(TRUE OR `site` =  :site) AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            $sql
        );
    }

}
