<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailRequestPasswordUpdate extends AbstractMailWithLink
{
    public function subject()
    {
        return _('login:: Forgot your password');
    }

    public function message()
    {
        return _('login:: Quelqu\'un a demande a reinitialiser le mode passe correspondant au login suivant : ')
        . "\n"
        .  _('login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien');
    }

    public function buttonText()
    {
        return _('Renew password');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
