<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\RecordsRequest;
use record_Interface;
use record_adapter;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class StatusChanged extends SfEvent
{
    /** @var record_adapter $record */
    private $record;
    /** @var  string $oldstatus */
    private $oldstatus;

    public function __construct(record_adapter $record, $oldstatus)
    {
        $this->record = $record;
        $this->oldstatus = $oldstatus;
    }

    /**
     * @return record_adapter
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @return string
     */
    public function getOldStatus()
    {
        return $this->oldstatus;
    }
}
