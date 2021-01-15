<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class PopulateIndexFailureEvent extends SfEvent
{
    private $host;
    private $port;
    private $indexName;
    private $databoxId;
    private $workerMessage;
    private $count;
    private $workerJobId;

    public function __construct($host, $port, $indexName, $databoxId, $workerMessage, $count, $workerJobId)
    {
        $this->host             = $host;
        $this->port             = $port;
        $this->indexName        = $indexName;
        $this->databoxId        = $databoxId;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
        $this->workerJobId      = $workerJobId;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getDataboxId()
    {
        return $this->databoxId;
    }

    public function getWorkerMessage()
    {
        return $this->workerMessage;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getWorkerJobId()
    {
        return $this->workerJobId;
    }
}
