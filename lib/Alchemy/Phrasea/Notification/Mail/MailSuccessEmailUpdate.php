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

class MailSuccessEmailUpdate extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return sprintf(_('Update of your email address on %s'), $this->getPhraseanetTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return sprintf("%s\n%s\n%s",
            sprintf(_('Dear %s,'), $this->receiver->getName()),
            _('Your contact email address has been updated'),
            $this->message
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->getPhraseanetTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app['url_generator']->generate('root', array(), true);
    }
}
