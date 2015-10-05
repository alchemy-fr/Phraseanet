<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectionLabelChanged extends CollectionRelated
{
    public function getLng()
    {
        return $this->args['lng'];
    }

    public function getLabelBefore()
    {
        return $this->args['label_before'];
    }
}
