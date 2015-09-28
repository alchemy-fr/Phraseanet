<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclGiveAccessToSbasEvent extends AclRelatedEvent
{
    public function getSbasId()
    {
        return $this->parms['sbas_id'];
    }
}
