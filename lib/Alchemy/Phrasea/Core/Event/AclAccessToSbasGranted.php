<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclAccessToSbasGranted extends AclRelated
{
    public function getSbasId()
    {
        return $this->args['sbas_id'];
    }
}
