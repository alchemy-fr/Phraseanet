<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;
use record_adapter;


class MediaSubstituted extends SfEvent
{
    /** @var  record_adapter */
    private $record;
    /** @var  string */
    private $subdefName;

    public function __construct(record_adapter $record, $subdefName)
    {
        $this->record = $record;
        $this->subdefName = $subdefName;
    }

    public function getRecord()
    {
        return $this->record;
    }

    public function getName()
    {
        return $this->subdefName;
    }
}
