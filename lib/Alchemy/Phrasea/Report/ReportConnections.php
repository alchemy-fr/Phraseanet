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
use Alchemy\Phrasea\Report\Report;
use Alchemy\Phrasea\Report\ReportInterface;


class ReportConnections extends Report implements ReportInterface
{
    private $appKey;

    public function  getSql()
    {
        switch($this->parms['group']) {
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
                    array_map(function($g) {return '`'.$g.'`';}, explode(',', $this->parms['group']))
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

        return str_replace(
            '{{GlobalFilter}}',
            // "`site` =  :site AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            "(TRUE OR `site` =  :site) AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            $sql
        );
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

}
