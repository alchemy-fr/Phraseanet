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


class ReportRootService
{
    use JsonBodyAware;

    private $acl;

    /**
     * @param \ACL $acl
     */
    public function __construct(\ACL $acl)
    {
        $this->acl = $acl;
    }

    public function getGranted()
    {
        $granted = [];

        /** @var \collection $collection */
        foreach ($this->acl->get_granted_base([\ACL::CANREPORT]) as $collection) {
            if (!isset($granted[$collection->get_sbas_id()])) {
                $granted[$collection->get_sbas_id()] = [
                    'id' => $collection->get_sbas_id(),
                    'name' => $collection->get_databox()->get_viewname(),
                    'collections' => []
                ];
            }
            $granted[$collection->get_sbas_id()]['collections'][] = [
                'id' => $collection->get_coll_id(),
                'base_id' => $collection->get_base_id(),
                'name' => $collection->get_name()
            ];
        }

        return $granted;
    }

}
