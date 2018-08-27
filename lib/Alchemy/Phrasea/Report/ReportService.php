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
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ReportService
{
    use JsonBodyAware;

    protected $appKey;
    protected $appbox;
    protected $acl;

    /**
     * @param string $appKey
     * @param \appbox $appbox
     * @param \ACL acl
     */
    public function __construct($appKey, \appbox $appbox, \ACL $acl)
    {
        $this->appKey = $appKey;
        $this->appbox = $appbox;
        $this->acl = $acl;
    }

    protected function playSql($sbasId, $sql, $parms)
    {
        $stmt = $this->findDbConnectionOr404($sbasId)->prepare($sql);
        $stmt->execute($parms);
        $ret = $stmt->fetchAll();
        $stmt->closeCursor();

        return $ret;
    }

    /**
     * get coll id's granted for report, possibly filtered by
     * baseIds : only from this list of bases
     *
     * @param int $sbasId
     * @param int[]|null $baseIds
     * @return array
     */
    protected function getCollIds($sbasId, $baseIds)
    {
        $ret = [];
         /** @var \collection $collection */
        foreach($this->acl->get_granted_base([\ACL::CANREPORT]) as $collection) {
            if($collection->get_sbas_id() != $sbasId) {
                continue;
            }
            if(!is_null($baseIds) && !in_array($collection->get_base_id(), $baseIds)) {
                continue;
            }
            $ret[] = $collection->get_coll_id();
        }

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
            throw new NotFoundHttpException(sprintf('Databox %s not found', $sbasId));
        }

        return $db->get_connection();
    }

}
