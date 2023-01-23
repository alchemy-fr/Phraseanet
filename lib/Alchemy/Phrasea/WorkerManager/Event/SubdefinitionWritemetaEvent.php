<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Model\RecordInterface;

class SubdefinitionWritemetaEvent extends RecordEvent
{
    const CREATE = 'create';
    const FAILED = 'failed';

    private $status;
    private $subdefName;
    private $workerMessage;
    private $count;
    private $workerJobId;

    public function __construct(RecordInterface $record, $subdefName, $status = self::CREATE, $workerMessage = '', $count = 2, $workerJobId = 0)
    {
        parent::__construct($record);

        $this->subdefName       = $subdefName;
        $this->status           = $status;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
        $this->workerJobId      = $workerJobId;
    }

    public function getSubdefName()
    {
        return $this->subdefName;
    }

    public function getStatus()
    {
        return $this->status;
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
