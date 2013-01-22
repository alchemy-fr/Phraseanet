<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailRequestPasswordSetup extends AbstractMailWithLink
{
    private $login;

    public function subject()
    {
        return sprintf(_('Your account on %s'), $this->app['phraseanet.registry']->get('GV_homeTitle'));
    }

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function message()
    {
        return sprintf(_('Your account with the login %s as been created'), $this->login)
            . "\n"
            . _('You now have to set up your pasword');
    }

    public function buttonText()
    {
        return _('Setup my password');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
