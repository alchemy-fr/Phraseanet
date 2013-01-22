<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoValidationReminder extends AbstractMailWithLink
{
    private $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function subject()
    {
        return sprintf(_("Reminder : validate '%s'"), $this->title);
    }

    public function message()
    {
        return sprintf(
            _('Il ne vous reste plus que %d jours pour terminer votre validation'),
            $this->app['phraseanet.registry']->get('GV_validation_reminder')
        );
    }

    public function buttonText()
    {
        return _('Validate');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
