<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailSuccessFTPSender extends AbstractMail
{
    private $server;

    public function setServer($server)
    {
        $this->server = $server;
    }

    public function subject()
    {
        return sprintf(
            _('task::ftp:Status about your FTP transfert from %1$s to %2$s'),
            $this->app['phraseanet.registry']->get('GV_homeTitle'), $this->server
        );
    }

    public function message()
    {
        return $this->message;
    }

    public function buttonText()
    {
    }

    public function buttonURL()
    {
    }
}
