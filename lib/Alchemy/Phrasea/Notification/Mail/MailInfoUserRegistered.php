<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoUserRegistered extends AbstractMail
{
    private $registeredUser;

    public function setRegisteredUser($registeredUser)
    {
        $this->registeredUser = $registeredUser;
    }

    public function subject()
    {
        return sprintf(
            _('admin::register: demande d\'inscription sur %s'), $this->app['phraseanet.registry']->get('GV_homeTitle')
        );
    }

    public function message()
    {
        return _('admin::register: un utilisateur a fait une demande d\'inscription')
        . "\n\n" .  sprintf('%s %s',$this->registeredUser->get_firstname(),  $this->registeredUser->get_lastname())
        . "\n\n" .  sprintf('%s %s',$this->registeredUser->get_job(),  $this->registeredUser->get_company());
    }

    public function buttonText()
    {
        return _('Process the registration');
    }

    public function buttonURL()
    {
        return sprintf('%sadmin/?section=registrations', $this->app['phraseanet.registry']->get('GV_ServerName'));
    }
}
