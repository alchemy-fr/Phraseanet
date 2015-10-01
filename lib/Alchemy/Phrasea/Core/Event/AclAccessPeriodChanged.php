<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclAccessPeriodChanged extends AclRelated
{
    public function getBaseId()
    {
        return $this->args['base_id'];
    }

    public function getLimit()
    {
        return $this->args['limit'];
    }

    public function getLimitFrom()
    {
        return $this->args['limit_from'];
    }

    public function getLimitTo()
    {
        return $this->args['limit_to'];
    }
}
