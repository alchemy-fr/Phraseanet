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

use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class FeedEntryEvent extends SfEvent
{
    private $entry;
    private $emailNotification;

    public function __construct(FeedEntry $entry, $emailNotification)
    {
        $this->entry = $entry;
        $this->emailNotification = (Boolean) $emailNotification;
    }

    public function getFeedEntry()
    {
        return $this->entry;
    }

    public function hasEmailNotification()
    {
        return $this->emailNotification;
    }
}
