<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use Alchemy\Phrasea\Model\Entities\User;
use DateTime;

class MailInfoValidationRequest extends AbstractMailWithLink
{
    /** @var string */
    private $title;
    /** @var User */
    private $user;
    /** @var bool */
    private $isVote;
    /** @var BasketParticipant */
    private $participant;
    /** @var DateTime|null */
    private $shareExpiresDate;
    /** @var DateTime|null */
    private $voteExpiresDate;

    /**
     * Sets the title of the validation
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets the user that asks for the validation
     *
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setParticipant($participant)
    {
        $this->participant = $participant;
    }

    public function setShareExpires($shareExpiresDate)
    {
        $this->shareExpiresDate = $shareExpiresDate;
    }

    public function setVoteExpires($voteExpiresDate)
    {
        $this->voteExpiresDate = $voteExpiresDate;
    }

    public function setIsVote($isVote)
    {
        $this->isVote = $isVote;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->user) {
            throw new LogicException('You must set an user before calling getSubject');
        }
        if (!$this->title) {
            throw new LogicException('You must set a title before calling getSubject');
        }

        return $this->app->trans("Validation request from %user% for '%title%'", ['%user%' => $this->user->getDisplayName(), '%title%' => $this->title], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        /* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        if (0 < $this->duration) {
            if (1 < $this->duration) {
                return $this->message . "\n\n" . $this->app->trans("You have %quantity% days to validate the selection.", ['%quantity%' => $this->duration], 'messages', $this->getLocale());
            } else {
                return $this->message . "\n\n" . $this->app->trans("You have 1 day to validate the selection.", [], 'messages', $this->getLocale());
            }
        }
        */
        // todo: convert dates back to days ?
        if(!is_null($this->shareExpiresDate)) {
            $this->message .= "\n\n" . $this->app->trans("mail::validation: Share will expire on %expire%", ['%expire%' => $this->shareExpiresDate->format("Y-m-d")], 'messages', $this->getLocale());
        }
        if(!is_null($this->voteExpiresDate)) {
            $this->message .= "\n\n" . $this->app->trans("mail:: validation: Vote will expire on %expire%", ['%expire%' => $this->voteExpiresDate->format("Y-m-d")], 'messages', $this->getLocale());
        }
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Start validation', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
