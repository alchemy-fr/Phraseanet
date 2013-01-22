<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailRequestEmailUpdate extends AbstractMailWithLink
{
    public function subject()
    {
        return _('login::register: sujet email : confirmation de votre adresse email');
    }

    public function message()
    {
        return _('admin::compte-utilisateur: email changement de mot d\'email Bonjour, nous avons bien recu votre demande de changement d\'adresse e-mail. Pour la confirmer, veuillez suivre le lien qui suit. SI vous recevez ce mail sans l\'avoir sollicite, merci de le detruire et de l\'ignorer.');
    }

    public function buttonText()
    {
        return _('Confirm new email address');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
