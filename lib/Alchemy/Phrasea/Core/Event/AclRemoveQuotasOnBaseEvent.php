<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclRemoveQuotasOnBaseEvent extends AclRelatedEvent
{
    public function getBaseId()
    {
        return $this->parms['base_id'];
    }
}
