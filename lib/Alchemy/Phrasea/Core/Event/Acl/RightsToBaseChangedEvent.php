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

class RightsToBaseChangedEvent extends AclEvent
{
    public function getBaseId()
    {
        return $this->args['base_id'];
    }

    public function getRights()
    {
        return $this->args['rights'];
    }
}
