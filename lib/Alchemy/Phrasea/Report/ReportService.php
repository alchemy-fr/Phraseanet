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


class ReportService
{
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

    /**
     * return bases allowed for reporting, grouped by databox
     * @return array
     */
    public function getGranted()
    {
        $databoxes = [];

        /** @var \collection $collection */
        foreach ($this->acl->get_granted_base([\ACL::CANREPORT]) as $collection) {
            $sbas_id = $collection->get_sbas_id();
            if (!isset($databoxes[$sbas_id])) {
                $databoxes[$sbas_id] = [
                    'id' => $sbas_id,
                    'name' => $collection->get_databox()->get_viewname(),
                    'collections' => []
                ];
            }
            $databoxes[$sbas_id]['collections'][$collection->get_base_id()] = [
                'id' => $collection->get_base_id(),
                'coll_id' => $collection->get_coll_id(),
                'name' => $collection->get_name()
            ];
        }

        return ['databoxes' => $databoxes];
    }

}
