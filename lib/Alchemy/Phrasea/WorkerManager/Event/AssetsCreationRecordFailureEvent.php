<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class AssetsCreationRecordFailureEvent extends SfEvent
{
    /** @var array */
    private $payload;
    private $workerMessage;
    private $count;

    public function __construct($payload, $workerMessage = '', $count = 2)
    {
        $this->payload          = $payload;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getWorkerMessage()
    {
        return $this->workerMessage;
    }

    public function getCount()
    {
        return $this->count;
    }
}
