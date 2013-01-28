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

class MailRequestEmailUpdate extends AbstractMailWithLink
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
        return _('admin::compte-utilisateur: email changement de mot d\'email Bonjour, nous avons bien recu votre demande de changement d\'adresse e-mail. Pour la confirmer, veuillez suivre le lien qui suit. SI vous recevez ce mail sans l\'avoir sollicite, merci de le detruire et de l\'ignorer.');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Confirm new email address');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
