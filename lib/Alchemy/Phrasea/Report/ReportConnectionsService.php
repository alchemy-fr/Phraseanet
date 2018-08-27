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


class ReportConnectionsService extends ReportService
{
    use JsonBodyAware;


    public function  getConnections($sbasId, $dmin, $dmax, $group)
    {
        $parms = [];
        switch($group) {
            case null:
                $sql = "SELECT * FROM `log`\n"
                    . " WHERE {{GlobalFilter}}";
                break;
            case 'user':
                $sql = "SELECT `usrid`, `user`, MIN(`date`) AS `dmin`, MAX(`date`) AS dmax, SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY `usrid`\n"
                    . " ORDER BY `nb` DESC\n"
                    // . " WITH ROLLUP"
                ;
                break;
            case 'nav':
            case 'nav,version':
            case 'os':
            case 'os,nav':
            case 'os,nav,version':
            case 'res':
                $group = implode(
                    ',',
                    array_map(function($g) {return '`'.$g.'`';}, explode(',', $group))
                );
                $sql = "SELECT " . $group . ", SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY " . $group . "\n"
                    . " ORDER BY `nb` DESC\n"
                    // . " WITH ROLLUP"
                ;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $sql = str_replace(
            '{{GlobalFilter}}',
            // "`site` =  :site AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            "(TRUE OR `site` =  :site) AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
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
