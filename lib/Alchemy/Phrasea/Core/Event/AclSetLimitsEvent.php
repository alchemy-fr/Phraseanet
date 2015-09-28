<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclSetLimitsEvent extends AclRelatedEvent
{
    public function getBaseId()
    {
        return $this->parms['base_id'];
    }

    public function getLimit()
    {
        return $this->parms['limit'];
    }

    public function getLimitFrom()
    {
        return $this->parms['limit_from'];
    }

    public function getLimitTo()
    {
        return $this->parms['limit_to'];
    }
}
