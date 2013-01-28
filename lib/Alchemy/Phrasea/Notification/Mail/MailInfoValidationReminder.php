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

class MailInfoValidationReminder extends AbstractMailWithLink
{
    private $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getSubject');
        }

        return sprintf(_("Reminder : validate '%s'"), $this->title);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return sprintf(
            _('Il ne vous reste plus que %d jours pour terminer votre validation'),
            $this->app['phraseanet.registry']->get('GV_validation_reminder')
        );
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
