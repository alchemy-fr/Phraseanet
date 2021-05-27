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


    public function __construct($dataType, $data, $databoxId)
    {
        $this->dataType      = $dataType;
        $this->data          = $data;
        $this->databoxId     = $databoxId;
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
}
