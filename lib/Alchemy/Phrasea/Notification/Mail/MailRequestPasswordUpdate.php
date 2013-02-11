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

class MailRequestPasswordUpdate extends AbstractMailWithLink
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return _('login:: Forgot your password');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return _('login:: Quelqu\'un a demande a reinitialiser le mode passe correspondant au login suivant : ')
        . "\n"
        .  _('login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Renew password');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
