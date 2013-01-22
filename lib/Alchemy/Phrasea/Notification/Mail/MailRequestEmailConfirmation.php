<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailRequestEmailConfirmation extends AbstractMailWithLink
{
    public function subject()
    {
        return _('login::register: sujet email : confirmation de votre adresse email');
    }

    public function message()
    {
        _('login::register: email confirmation email Pour valider votre inscription a la base de donnees, merci de confirmer votre e-mail en suivant le lien ci-dessous.');
    }

    public function getExpirationMessage()
    {
        return sprintf(
            _('Attention, ce lien lien est valable jusqu\'au %s %s'),
            $this->app['date-formatter']->getDate($this->expiration),
            $this->app['date-formatter']->getTime($this->expiration)
        );
    }

    public function buttonText()
    {
        return _('Validate e-mail address');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
