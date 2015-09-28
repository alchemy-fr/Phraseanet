<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclUpdateRightsToSbasEvent extends AclRelatedEvent
{
    public function getSbasId()
    {
        return $this->parms['sbas_id'];
    }

    public function  getRights()
    {
        return $this->parms['rights'];
    }
}
