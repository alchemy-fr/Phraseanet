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

class MailSuccessEmailConfirmationUnregistered extends AbstractMailWithLink
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return _('Email successfully confirmed');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return _('login::register: merci d\'avoir confirme votre adresse email')
            . "\n"
            . _("You have to wait for an administrator approval for your access request");
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Watch my access requests status');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app['url_generator']->generate('account_access', array(), true);
    }
}
