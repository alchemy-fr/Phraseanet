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

    public function  getSql()
    {
        switch ($this->parms['group']) {
            case null:
                $sql = "SELECT `ld`.`id`, `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                    . "        `ld`.`date`, `ld`.`record_id`, `ld`.`coll_id`"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}";
                break;
            case 'user':
                $sql = "SELECT `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC\n"// . " WITH ROLLUP"
                ;
                break;
            case 'record':
                $sql = "SELECT `ld`.`record_id`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC\n"// . " WITH ROLLUP"
                ;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $collIds = $this->getCollIds($this->acl, $this->parms['bases']);
        $collIds = join(',', $collIds);

        $sql = str_replace(
            '{{GlobalFilter}}',
            "`action`='download' AND `ld`.`coll_id` IN(" . $collIds . ")\n"
            . " AND (TRUE OR `l`.`site` =  :site) AND !ISNULL(`l`.`usrid`) AND `ld`.`date` >= :dmin AND `ld`.`date` <= :dmax\n"
            . " AND `ld`.`final`='document'",
            $sql
        );

        return $sql;
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

}
