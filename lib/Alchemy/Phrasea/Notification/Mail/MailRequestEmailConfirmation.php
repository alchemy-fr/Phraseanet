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

class MailRequestEmailConfirmation extends AbstractMailWithLink
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return _('login::register: sujet email : confirmation de votre adresse email');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return _('login::register: email confirmation email Pour valider votre inscription a la base de donnees, merci de confirmer votre e-mail en suivant le lien ci-dessous.');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Validate e-mail address');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
