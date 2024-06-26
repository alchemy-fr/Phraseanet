<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class RecordEvent extends Event
{
    private $record;
    private $initiatorId;

    public function __construct(RecordInterface $record, $initiatorId = null)
    {
        $this->record = $record;
        $this->initiatorId = $initiatorId;
    }

    /**
     * @return RecordInterface
     */
    public function getRecord()
    {
        return $this->record;
    }

    public function getInitiatorId()
    {
        return $this->initiatorId;
    }
}
