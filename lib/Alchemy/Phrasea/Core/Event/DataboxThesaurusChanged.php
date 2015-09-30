<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class DataboxThesaurusChanged extends DataboxRelated
{
    public function getDomBefore()
    {
        return $this->args['dom_before'];
    }
}
