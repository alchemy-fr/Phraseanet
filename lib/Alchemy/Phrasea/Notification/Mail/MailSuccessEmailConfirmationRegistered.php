<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailSuccessEmailConfirmationRegistered extends AbstractMailWithLink
{
    public function subject()
    {
        return _('Email successfully confirmed');
    }

    public function message()
    {
        return _('login::register: merci d\'avoir confirme votre adresse email');
    }

    public function buttonText()
    {
        return sprintf(_('Access %'), $this->app['phraseanet.registry']->get('GV_homeTile'));
    }

    public function buttonURL()
    {
        return $this->app['phraseanet.registry']->get('GV_ServerName');
    }
}
