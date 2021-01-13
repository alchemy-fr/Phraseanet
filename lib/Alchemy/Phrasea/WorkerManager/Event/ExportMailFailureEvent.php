<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class ExportMailFailureEvent extends SfEvent
{
    private $emitterUserId;
    private $tokenValue;
    private $destinationMails;
    private $params;
    private $workerMessage;
    private $count;

    public function __construct($emitterUserId, $tokenValue, $destinationMails, $params, $workerMessage, $count)
    {
        $this->emitterUserId    = $emitterUserId;
        $this->tokenValue       = $tokenValue;
        $this->destinationMails = $destinationMails;
        $this->params           = $params;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
    }

    public function getEmitterUserId()
    {
        return $this->emitterUserId;
    }

    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    public function getDestinationMails()
    {
        return $this->destinationMails;
    }

    public function getParams()
    {
        return $this->params;
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
