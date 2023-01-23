<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

class StatusChangedEvent extends RecordEvent
{
    /** @var array  */
    private $statusBefore;

    /** @var array  */
    private $statusAfter;

    public function __construct(\record_adapter $record, array $statusBefore, array $statusAfter)
    {
        parent::__construct($record);
        $this->statusBefore = $statusBefore;
        $this->statusAfter  = $statusAfter;
    }

    public function getStatusBefore()
    {
        return $this->statusBefore;
    }

    public function getStatusAfter()
    {
        return $this->statusAfter;
    }
}
