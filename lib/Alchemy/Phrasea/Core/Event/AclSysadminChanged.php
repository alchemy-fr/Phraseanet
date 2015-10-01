<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclSysadminChanged extends AclRelated
{
    public function isSysadmin()
    {
        return $this->args['is_sysadmin'];
    }
}
