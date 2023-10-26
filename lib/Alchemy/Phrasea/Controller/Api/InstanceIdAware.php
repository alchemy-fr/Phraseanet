<?php

namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

trait InstanceIdAware
{
    private $instanceId;

    /**
     * @param PropertyAccess $conf
     * @return InstanceIdAware
     */
    public function setInstanceId(PropertyAccess $conf)
    {
        $this->instanceId = $conf->get(
            ['phraseanet-service', 'phraseanet_local_id'],
            md5($conf->get(['main', 'key'], ''))
        );

        return $this;
    }

    public function getResourceIdResolver()
    {
        return function(\record_adapter $record): string {
            return $this->instanceId . '_' . $record->getDataboxId() . '_' . $record->getRecordId();
        };
    }
}
