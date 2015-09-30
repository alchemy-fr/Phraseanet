<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectionUnmounted extends CollectionRelated
{
    public function getCollId()
    {
        return $this->args['coll_id'];
    }

    public function getName()
    {
        return $this->args['name'];
    }
}
