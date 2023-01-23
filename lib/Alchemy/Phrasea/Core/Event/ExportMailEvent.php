<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class ExportMailEvent extends SfEvent
{
    private $emitterUserId;
    private $tokenValue;
    /** @var array  */
    private $destinationMails;
    /** @var  array */
    private $params;


    public function __construct($emitterUserId, $tokenValue, array $destMails, array $params)
    {
        $this->emitterUserId     = $emitterUserId;
        $this->tokenValue        = $tokenValue;
        $this->destinationMails  = $destMails;
        $this->params            = $params;
    }

    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    public function getDestinationMails()
    {
        return $this->destinationMails;
    }

    public function getEmitterUserId()
    {
        return $this->emitterUserId;
    }

    public function getParams()
    {
        return $this->params;
    }
}
