<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailRecordsExport extends AbstractMailWithLink
{
    public function subject()
    {
        return _('Vous avez recu des documents');
    }

    public function message()
    {
        return $this->message;
    }

    public function getExpirationMessage()
    {
        return sprintf(
            _('Attention, ce lien lien est valable jusqu\'au %s %s'),
            $this->app['date-formatter']->getDate($this->expiration),
            $this->app['date-formatter']->getTime($this->expiration)
        );
    }

    public function buttonText()
    {
        return _('Download');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
