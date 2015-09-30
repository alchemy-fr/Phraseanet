<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class DataboxTouChanged extends DataboxRelated
{
    public function getTouBefore()
    {
        return $this->args['tou_before'];
    }
}
