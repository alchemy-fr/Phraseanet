<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectionNameChanged extends CollectionRelated
{
    public function getNameBefore()
    {
        return $this->args['name_before'];
    }
}
