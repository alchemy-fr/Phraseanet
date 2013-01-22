<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailSuccessEmailConfirmationUnregistered extends AbstractMailWithLink
{

    public function subject()
    {
        return _('Email successfully confirmed');
    }

    public function message()
    {
        return _('login::register: merci d\'avoir confirme votre adresse email')
            . "\n"
            . _("You have to wait for an administrator approval for your access request");
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
