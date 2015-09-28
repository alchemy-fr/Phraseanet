<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclUpdateRightsToBaseEvent extends AclRelatedEvent
{
    public function getBaseId()
    {
        return $this->parms['base_id'];
    }

    public function  getRights()
    {
        return $this->parms['rights'];
    }
}
