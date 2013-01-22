<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoSomebodyAutoregistered extends AbstractMailWithLink
{
    public function subject()
    {
        return sprintf(
            _('admin::register: Inscription automatique sur %s'),
            $this->app['phraseanet.registry']->get('GV_homeTitle')
        );
    }

    public function message()
    {
        return _('admin::register: un utilisateur s\'est inscrit')."\n\n".$this->message;
    }

    public function buttonText()
    {
        return _('Update the account');
    }

    public function buttonURL()
    {
        return $this->app['phraseanet.registry']->get('GV_ServerName') . 'admin/?section=users';
    }
}
