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


abstract class Report
{
    /** @var  \databox */
    protected $databox;
    protected $parms;

    protected $sql = null;
    protected $sqlParms = null;

    public function __construct(\databox $databox, $parms)
    {
        $this->databox = $databox;
        $this->parms = $parms;
    }

    abstract function getSql();

    abstract function getSqlParms();

    public function getRows()
    {
        return new ReportRows(
            $this->databox->get_connection(),
            $this->getSql(),
            $this->getSqlParms(),
            'id'
        );
    }

    public function getContent()
    {
        $ret = [];
        foreach($this->getRows() as $k=>$r) {
            $ret[] = $r;
        }

        return $ret;
    }

    /**
     * get coll id's granted for report, possibly filtered by
     * baseIds : only from this list of bases
     *
     * @param \ACL $acl
     * @param int[]|null $baseIds
     * @return array
     */
    protected function getCollIds(\ACL $acl, $baseIds)
    {
        $ret = [];
        /** @var \collection $collection */
        foreach($acl->get_granted_base([\ACL::CANREPORT]) as $collection) {
            if($collection->get_sbas_id() != $this->databox->get_sbas_id()) {
                continue;
            }
            if(!is_null($baseIds) && !in_array($collection->get_base_id(), $baseIds)) {
                continue;
            }
            $ret[] = $this->databox->get_connection()->quote($collection->get_coll_id());
        }

        return $ret;
    }

}
