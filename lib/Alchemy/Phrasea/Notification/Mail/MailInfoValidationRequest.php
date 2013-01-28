<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoValidationRequest extends AbstractMailWithLink
{
    private $title;
    private $user;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setUser($user)
    {
        $this->user = $user;
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
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Validate');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
