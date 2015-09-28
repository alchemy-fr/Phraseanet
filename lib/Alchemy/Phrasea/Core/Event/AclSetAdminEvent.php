<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclSetAdminEvent extends AclRelatedEvent
{
    public function isAdmin()
    {
        return $this->parms['is_admin'];
    }
}
