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
    private $fileSize;

    public function __construct(RecordInterface $record, $subdefName, $fileSize = 0, $status = self::CREATE, $workerMessage = '', $count = 2, $workerJobId = 0)
    {
        parent::__construct($record);

        $this->subdefName       = $subdefName;
        $this->status           = $status;
        $this->workerMessage    = $workerMessage;
        $this->count            = $count;
        $this->workerJobId      = $workerJobId;

        /** @var \media_subdef $subdef */
        $subdef = $this->getRecord()->get_subdef($this->subdefName);
        if ($fileSize == 0 && $subdef->is_physically_present()) {
            $this->fileSize = filesize($subdef->getRealPath());
        } else {
            $this->fileSize = $fileSize;
        }
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

    public function getFileSize()
    {
        return $this->fileSize;
    }
}
