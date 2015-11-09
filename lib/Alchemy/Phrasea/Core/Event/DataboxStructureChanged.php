<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class DataboxStructureChanged extends DataboxRelated
{
    public function getDomBefore()
    {
        return $this->args['dom_before'];
    }
}
