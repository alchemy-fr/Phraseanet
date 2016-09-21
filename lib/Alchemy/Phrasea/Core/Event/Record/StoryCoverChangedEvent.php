<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

use record_adapter;
use Symfony\Component\EventDispatcher\Event;

class StoryCoverChangedEvent extends Event
{
    /** @var record_adapter $record */
    private $story_record;
    /** @var record_adapter $record */
    private $cover_record;

    public function __construct(record_adapter $story_record, record_adapter $cover_record)
    {
        $this->story_record = $story_record;
        $this->cover_record = $cover_record;
    }

    /**
     * @return record_adapter
     */
    public function getStoryRecord()
    {
        return $this->story_record;
    }

    /**
     * @return record_adapter
     */
    public function getCoverRecord()
    {
        return $this->cover_record;
    }
}
