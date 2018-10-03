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

class BridgeUploadFailureEvent extends SfEvent
{
    private $element;
    private $reason;

    public function __construct(\Bridge_Element $element, $reason)
    {
        $this->element = $element;
        $this->reason = $reason;
    }

    /**
     * @return \Bridge_Element
     */
    public function getElement()
    {
        return $this->element;
    }

    public function getReason()
    {
        return $this->reason;
    }
}
