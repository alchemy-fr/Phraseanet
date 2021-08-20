<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Model\RecordInterface;

class SubdefinitionCreationFailureEvent extends RecordEvent
{
    private $subdefName;
    private $workerMessage;
    private $count;
    private $workerJobId;
    private $documentFileSize;

    public function __construct(RecordInterface $record, $subdefName, $documentFileSize, $workerMessage, $count, $workerJobId)
    {
        parent::__construct($record);

        $this->subdefName       = $subdefName;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
        $this->workerJobId      = $workerJobId;
        $this->documentFileSize = $documentFileSize;
    }

    public function getSubdefName()
    {
        return $this->subdefName;
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

    public function getDocumentFileSize()
    {
        return $this->documentFileSize;
    }
}
