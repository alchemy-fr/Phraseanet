<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class RecordEditInWorkerEvent extends SfEvent
{
    private $mdsParams;
    private $elementKeys;
    private $databoxId;

    public function __construct($mdsParams, $elementKeys, $databoxId)
    {
        $this->mdsParams     = $mdsParams;
        $this->elementKeys   = $elementKeys;
        $this->databoxId     = $databoxId;
    }

    public function getMdsParams()
    {
        return $this->mdsParams;
    }

    public function getElementKeys()
    {
        return $this->elementKeys;
    }

    public function getDataboxId()
    {
        return $this->databoxId;
    }
}
