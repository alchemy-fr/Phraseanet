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

class MailSuccessAccessRequest extends AbstractMailWithLink
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return sprintf(_('login::register:email: Votre compte %s'), $this->getPhraseanetTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return _('login::register:email: Voici un compte rendu du traitement de vos demandes d\'acces :')
            . "\n"
            . $this->message;
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
