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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Class ReportFactory
 *
 * published as service $app['report.factory']
 *
 * @package Alchemy\Phrasea\Report
 */
class ReportFactory
{
    const CONNECTIONS = 'connections';
    const DOWNLOADS   = 'downloads';
    const RECORDS     = 'records';

    protected $appKey;
    protected $appbox;
    protected $databox;
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

    /**
     * @param $table
     * @param null $sbasId
     * @param null $parms
     *
     * @return ReportConnections | ReportActions
     */
    public function createReport($table, $sbasId=null, $parms=null)
    {
        switch($table) {
            case self::CONNECTIONS:
                return (new ReportConnections(
                        $this->findDbOr404($sbasId),
                        $parms
                    ))
                    ->setAppKey($this->appKey)
                    ;
                break;

            case self::DOWNLOADS:
                return (new ReportActions(
                        $this->findDbOr404($sbasId),
                        $parms
                    ))
                    ->setAppKey($this->appKey)
                    ->setACL($this->acl)
                    ->setAsDownloadReport(true)
                    ;
                break;

            case self::RECORDS:
                return (new ReportRecords(
                        $this->findDbOr404($sbasId),
                        $parms
                    ))
                    ->setACL($this->acl)
                    ;
                break;

            default:
                throw new \InvalidArgumentException(sprintf("unknown table type \"%s\"", $table));
                break;
        }
    }

    /**
     * @param int $sbasId
     * @return \databox
     */
    private function findDbOr404($sbasId)
    {
        $db = $this->appbox->get_databox(($sbasId));
        if(!$db) {
            throw new NotFoundHttpException(sprintf('Databox %s not found', $sbasId));
        }

        return $db;
    }

}
