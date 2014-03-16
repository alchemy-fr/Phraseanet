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

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;

class ValidationEvent extends PushEvent
{
    private $participant;
    private $duration;

    public function __construct(ValidationParticipant $participant, Basket $basket, $url, $message = null, $receipt = false, $duration = 0)
    {
        parent::__construct($basket, $message, $url, $receipt);
        $this->participant = $participant;
        $this->duration = $duration;
    }

    /**
     * @return ValidationParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @return null|integer
     */
    public function getDuration()
    {
        return $this->duration;
    }
}
