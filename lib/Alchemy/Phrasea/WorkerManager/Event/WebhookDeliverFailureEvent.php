<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class WebhookDeliverFailureEvent extends SfEvent
{
    private $webhookEventId;
    private $workerMessage;
    private $count;
    private $deleveryId;

    public function __construct($webhookEventId, $workerMessage, $count, $deleveryId = null)
    {
        $this->webhookEventId   = $webhookEventId;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
        $this->deleveryId       = $deleveryId;
    }

    public function getWebhookEventId()
    {
        return $this->webhookEventId;
    }

    public function getWorkerMessage()
    {
        return $this->workerMessage;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getDeleveryId()
    {
        return $this->deleveryId;
    }
}
