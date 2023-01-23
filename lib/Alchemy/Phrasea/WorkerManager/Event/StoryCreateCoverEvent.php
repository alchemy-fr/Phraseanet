<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class StoryCreateCoverEvent extends SfEvent
{
    private $data;

    public function __construct($data)
    {
        $this->data     = $data;
    }

    public function getData()
    {
        return $this->data;
    }

}
