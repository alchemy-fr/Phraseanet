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

class MailInfoValidationDone extends AbstractMailWithLink
{
    private $title;
    private $user;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setUser(\User_Adapter $user)
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
            throw new LogicException('You must set an title before calling getSubject');
        }

        return sprintf(
            _('push::mail:: Rapport de validation de %1$s pour %2$s'),
            $this->user->get_display_name(),
            $this->title
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->user) {
            throw new LogicException('You must set an user before calling getMessage');
        }

        return sprintf(
            _('%s has just sent its validation report, you can now see it'),
            $this->user->get_display_name()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('See validation results');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
