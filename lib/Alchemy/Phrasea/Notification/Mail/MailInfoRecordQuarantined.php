<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoRecordQuarantined extends AbstractMail
{
    public function subject()
    {
        return _('A document has been quarantined');
    }

    public function message()
    {
        return _('A file has been thrown to the quarantine.');
    }

    public function buttonText()
    {
        return _('Access quarantine');
    }

    public function buttonURL()
    {
        return sprintf('%sprod/', $this->app['phraseanet.registry']->get('GV_ServerName'));
    }
}
