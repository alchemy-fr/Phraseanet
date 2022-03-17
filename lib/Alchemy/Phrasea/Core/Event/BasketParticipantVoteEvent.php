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

use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use DateTime;

class BasketParticipantVoteEvent extends PushEvent
{
    private $participant;
    private $duration;
    private $isVote;
    private $shareExpires;
    private $voteExpires;

    public function __construct(BasketParticipant $participant, $url, $message = null, $receipt = false, $duration = 0, $isVote=null, $shareExpires=null, $voteExpires=null)
    {
        parent::__construct($participant->getBasket(), $message, $url, $receipt);
        $this->participant = $participant;
        $this->duration = $duration;
        $this->isVote = $isVote;
        $this->shareExpires = $shareExpires;
        $this->voteExpires = $voteExpires;
    }

    /**
     * @return BasketParticipant
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

    /**
     * @return DateTime|null
     */
    public function getShareExpires()
    {
        return $this->shareExpires;
    }

    /**
     * @return DateTime|null
     */
    public function getVoteExpires()
    {
        return $this->voteExpires;
    }

    /**
     * @return bool
     */
    public function getIsVote()
    {
        return $this->isVote;
    }
}
