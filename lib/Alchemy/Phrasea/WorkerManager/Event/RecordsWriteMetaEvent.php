<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Contracts\EventDispatcher\Event as SfEvent;

class RecordsWriteMetaEvent extends SfEvent
{
    private $recordIds;
    private $databoxId;

    public function __construct(array $recordIds, $databoxId)
    {
        $this->databoxId = $databoxId;
        $this->recordIds = $recordIds;
    }

    public function getRecordIds()
    {
        return $this->recordIds;
    }

    public function getDataboxId()
    {
        return $this->databoxId;
    }
}
