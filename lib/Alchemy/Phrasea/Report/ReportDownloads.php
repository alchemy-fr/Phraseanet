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


class ReportDownloads extends Report implements ReportInterface
{
    private $appKey;

    /** @var  \ACL */
    private $acl;

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

    public function setACL($acl)
    {
        $this->acl = $acl;

        return $this;
    }

    private function computeVars()
    {
        if(!is_null($this->sql)) {
            // vars already computed
            return;
        }

        switch ($this->parms['group']) {
            case null:
                $this->name = "Downloads";
                $this->columnTitles = ['id', 'usrid', 'user', 'fonction', 'societe', 'activite', 'pays', 'date', 'record_id', 'coll_id'];
                $sql = "SELECT `ld`.`id`, `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                    . "        `ld`.`date`, `ld`.`record_id`, `ld`.`coll_id`"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                ;
                $this->keyName = 'id';
                break;
            case 'user':
                $this->name = "Downloads by user";
                $this->columnTitles = ['usrid', 'user', 'fonction', 'societe', 'activite', 'pays', 'min_date', 'max_date', 'nb'];
                $sql = "SELECT `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC"
                ;
                $this->keyName = 'usrid';
                break;
            case 'record':
                $this->name = "Downloads by record";
                $this->columnTitles = ['record_id', 'min_date', 'max_date', 'nb'];
                $sql = "SELECT `ld`.`record_id`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC"
                ;
                $this->keyName = 'record_id';
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $collIds = $this->getCollIds($this->acl, $this->parms['bases']);
        $collIds = join(',', $collIds);

        $this->sql = str_replace(
            '{{GlobalFilter}}',
            "`action`='download' AND `ld`.`coll_id` IN(" . $collIds . ")\n"
            . " AND (TRUE OR `l`.`site` =  :site) AND !ISNULL(`l`.`usrid`) AND `ld`.`date` >= :dmin AND `ld`.`date` <= :dmax\n"
            . " AND `ld`.`final`='document'",
            $sql
        );
    }

}
