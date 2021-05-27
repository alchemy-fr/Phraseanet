<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class RecordEditInWorkerEvent extends SfEvent
{
    const MDS_TYPE = 'mds_type';
    const JSON_TYPE = 'json_type';

    private $dataType;
    private $data;
    private $databoxId;
    private $sessionLogId;

    public function __construct($dataType, $data, $databoxId, $sessionLogId)
    {
        $this->dataType      = $dataType;
        $this->data          = $data;
        $this->databoxId     = $databoxId;
        $this->sessionLogId  = $sessionLogId;
    }

    public function getDataType()
    {
        return $this->dataType;
    }
    public function getData()
    {
        return $this->data;
    }

    public function getDataboxId()
    {
        return $this->databoxId;
    }

    public function getSessionLogId()
    {
        return $this->sessionLogId;
    }
}
