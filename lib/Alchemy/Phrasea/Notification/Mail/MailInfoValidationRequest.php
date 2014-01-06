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

class MailInfoValidationRequest extends AbstractMailWithLink
{
    /** @var string */
    private $title;
    /** @var \User_Adapter */
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

        return sprintf(_("Validation request from %s : '%s'"), $this->user->get_display_name(), $this->title);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (0 < $this->duration) {
            if (1 < $this->duration) {
                return $this->message . "\n\n" . sprintf(_("You have %d days to validate the selection."), $this->duration);
            } else {
                return $this->message . "\n\n" . _("You have 1 day to validate the selection.");
            }
        }

        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Start validation');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
