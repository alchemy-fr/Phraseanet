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

class MailSuccessEmailConfirmationRegistered extends AbstractMailWithLink
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
        return _('login::register: merci d\'avoir confirme votre adresse email');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return sprintf(_('Access %'), $this->app['phraseanet.registry']->get('GV_homeTile'));
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app['url_generator']->generate('root', array(), true);
    }
}
