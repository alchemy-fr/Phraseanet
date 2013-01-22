<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoNewOrder extends AbstractMail
{
    private $user;

    public function setUser(\User_Adapter $user)
    {
        $this->user = $user;
    }

    public function subject()
    {
        return sprintf(
            _('admin::register: Nouvelle commande sur %s'),
            $this->app['phraseanet.registry']->get('GV_homeTitle')
        );
    }

    public function message()
    {
        return sprintf(_('%s has ordered documents'),$this->user->get_display_name());
    }

    public function buttonText()
    {
        return sprintf(_('See order on %s'), $this->app['phraseanet.registry']->get('GV_homeTitle'));
    }

    public function buttonURL()
    {
        return sprintf('%sprod', $this->app['phraseanet.registry']->get('GV_ServerName'));
    }
}
