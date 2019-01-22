<?php

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class ApiLoadEndEvent extends SfEvent
{
    public function getName()
    {
        return PhraseaEvents::API_LOAD_END;
    }
}
