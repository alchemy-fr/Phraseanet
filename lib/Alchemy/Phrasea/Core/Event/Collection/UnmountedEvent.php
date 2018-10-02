<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Collection;

class UnmountedEvent extends CollectionEvent
{
    public function getCollId()
    {
        return $this->args['coll_id'];
    }

    public function getCollName()
    {
        return $this->args['coll_name'];
    }
}
