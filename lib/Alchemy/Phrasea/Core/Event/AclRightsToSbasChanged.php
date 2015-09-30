<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclRightsToSbasChanged extends AclRelated
{
    public function getSbasId()
    {
        return $this->args['sbas_id'];
    }

    public function getRights()
    {
        return $this->args['rights'];
    }
}
