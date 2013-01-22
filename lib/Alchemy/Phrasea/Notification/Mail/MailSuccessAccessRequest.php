<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailSuccessAccessRequest extends AbstractMailWithLink
{

    public function subject()
    {
        return sprintf(_('login::register:email: Votre compte %s'), $this->app['phraseanet.registry']->get('GV_homeTitle'));
    }

    public function message()
    {
        return _('login::register:email: Voici un compte rendu du traitement de vos demandes d\'acces :')
            . "\n"
            . $this->message;
    }

    public function buttonText()
    {
        return _('Watch my access requests status');
    }

    public function buttonURL()
    {
        return $this->app['url_generator']->generate('account_access');
    }
}
