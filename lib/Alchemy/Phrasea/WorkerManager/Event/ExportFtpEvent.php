<?php

namespace  Alchemy\Phrasea\WorkerManager\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class ExportFtpEvent extends SfEvent
{
    private $ftpExportId;

    public function __construct($ftpExportId)
    {
        $this->ftpExportId = $ftpExportId;
    }

    public function getFtpExportId()
    {
        return $this->ftpExportId;
    }
}
