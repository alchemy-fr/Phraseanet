<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailTest extends AbstractMail
{
    public function subject()
    {
        return _('mail:: test d\'envoi d\'email');
    }

    public function message()
    {
        return sprintf("%s\n%s", sprintf(
            _('Ce mail est un test d\'envoi de mail depuis %s'),
            $this->registry->get('GV_ServerName')
        ), $this->message);
    }

    public function buttonText()
    {
        return $this->app['phraseanet.registry']->get('GV_homeTitle');
    }

    public function buttonURL()
    {
        return $this->registry->get('GV_ServerName');
    }
}
