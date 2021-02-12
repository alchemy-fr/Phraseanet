<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        return $this->app->trans('login::register: sujet email : confirmation de votre adresse email', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('login::register: email confirmation email Pour valider votre inscription a la base de donnees, merci de confirmer votre e-mail en suivant le lien ci-dessous.', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Validate e-mail address', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
