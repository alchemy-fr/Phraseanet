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
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Exception\InvalidArgumentException;


class ReportDownloadsService extends ReportService
{
    use JsonBodyAware;


    public function  getDownloads($sbasId, $dmin, $dmax, $group, $bases)
    {
        $parms = [];

        $conn = $this->findDbConnectionOr404($sbasId);
        switch($group) {
            case null:
                $sql = "SELECT `ld`.`id`, `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                    . "        `ld`.`date`, `ld`.`record_id`, `ld`.`coll_id`"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    ;
                break;
            case 'user':
                $sql = "SELECT `l`.`usrid`, `l`.`user`, `l`.`fonction`, `l`.`societe`, `l`.`activite`, `l`.`pays`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC\n"
                    // . " WITH ROLLUP"
                ;
                break;
            case 'record':
                $sql = "SELECT `ld`.`record_id`,\n"
                    . "        MIN(`ld`.`date`) AS `dmin`, MAX(`ld`.`date`) AS `dmax`, SUM(1) AS `nb`\n"
                    . " FROM `log_docs` AS `ld` INNER JOIN `log` AS `l` ON `l`.`id`=`ld`.`log_id`\n"
                    . " WHERE {{GlobalFilter}}"
                    . " GROUP BY `l`.`usrid`\n"
                    . " ORDER BY `nb` DESC\n"
                    // . " WITH ROLLUP"
                ;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $collIds = $this->getCollIds($sbasId, $bases);
        $collIds = join(',', array_map(function($collId) use($conn) {return $conn->quote($collId);}, $collIds));
        $sql = str_replace(
            '{{GlobalFilter}}',
            "`action`='download' AND `ld`.`coll_id` IN(" . $collIds . ")\n"
            . " AND (TRUE OR `l`.`site` =  :site) AND !ISNULL(`l`.`usrid`) AND `ld`.`date` >= :dmin AND `ld`.`date` <= :dmax\n"
            . " AND `ld`.`final`='document'",
            $sql
        );
        $parms = array_merge(
            $parms,
            [   ':site' => $this->appKey,
                ':dmin' => $dmin,
                ':dmax' => $dmax
            ]
        );

        return $this->playSql($sbasId, $sql, $parms);
    }

}
