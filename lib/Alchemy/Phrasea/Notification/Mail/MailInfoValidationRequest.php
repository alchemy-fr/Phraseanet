<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Model\Entities\User;

class MailInfoValidationRequest extends AbstractMailWithLink
{
    /** @var string */
    private $title;
    /** @var User */
    private $user;
    /** @var integer */
    private $duration;

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
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setDuration($duration)
    {
        $this->duration = (int) $duration;
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

        return $this->app->trans("Validation request from %user% for '%title%'", ['%user%' => $this->user->getDisplayName(), '%title%' => $this->title]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (0 < $this->duration) {
            if (1 < $this->duration) {
                return $this->message . "\n\n" . $this->app->trans("You have %d days to validate the selection.", ['%quantity%' => $this->duration]);
            } else {
                return $this->message . "\n\n" . $this->app->trans("You have 1 day to validate the selection.");
            }
        }

        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Start validation');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
