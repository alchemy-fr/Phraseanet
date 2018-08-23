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
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ReportConnectionsService
{
    use JsonBodyAware;

    private $appKey;
    private $appbox;

    /**
     * @param string $appKey
     * @param \appbox $appbox
     */
    public function __construct($appKey, \appbox $appbox)
    {
        $this->appKey = $appKey;
        $this->appbox = $appbox;

        //parent::__construct($app);
        //$id = $this->getAuthenticator()->getUser()->getId();
        //$app->getAuthenticatedUser();
        // $this->getAuthenticatedUser()->isAdmin();
    }

    public function  getConnections(Request $request, $sbasId)
    {
        $parms = [];
        $group = $request->get('group');
        switch($group) {
            case null:
                $sql = "SELECT * FROM `log`\n"
                    . " WHERE {{GlobalFilter}}";
                break;
            case 'user':
                $sql = "SELECT `usrid`, `user`, MIN(`date`) AS `dmin`, MAX(`date`) AS dmax, SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY `usrid`\n"
                    // . " ORDER BY nb ASC\n"
                    . " WITH ROLLUP";
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
                $sql = "SELECT ".$group.", SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY ".$group."\n"
                    // . " ORDER BY nb ASC\n"
                    . " WITH ROLLUP"
                ;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $sql = str_replace(
            '{{GlobalFilter}}',
            "`site` =  :site AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            $sql
        );
        $parms = array_merge(
            $parms,
            [   ':site' => $this->appKey,
                ':dmin' => $request->get('dmin'),
                ':dmax' => $request->get('dmax')
            ]
        );

        return $this->playSql($sbasId, $sql, $parms);
    }

    private function playSql($sbasId, $sql, $parms)
    {
        $stmt = $this->findDbConnectionOr404($sbasId)->prepare($sql);
        $stmt->execute($parms);
        $ret = $stmt->fetchAll();
        $stmt->closeCursor();

        return $ret;
    }

    /**
     * @param int $sbasId
     * @return Connection
     */
    protected function findDbConnectionOr404($sbasId)
    {
        $db = $this->appbox->get_databox(($sbasId));
        if(!$db) {
            throw new NotFoundHttpException('Order not found');
        }

        return $db->get_connection();
    }

}
