<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Acl;

class SysadminChangedEvent extends AclEvent
{
    public function isSysadmin()
    {
        return $this->args['is_sysadmin'];
    }
}
