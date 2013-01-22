<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailSuccessEmailUpdate extends AbstractMail
{
    public function subject()
    {
        return sprintf(_('Update of your email address on %s'), $this->app['phraseanet.registry']->get('GV_homeTitle'));
    }

    public function message()
    {
        return sprintf("%s\n%s\n%s",
            sprintf(_('Dear %s,'), $this->receiver->name()),
            _('Your contact email address has been updated'),
            $this->message
        );
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
