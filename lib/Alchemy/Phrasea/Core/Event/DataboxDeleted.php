<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class DataboxDeleted extends DataboxRelated
{
    public function getDbName()
    {
        return $this->args['dbname'];
    }
}
