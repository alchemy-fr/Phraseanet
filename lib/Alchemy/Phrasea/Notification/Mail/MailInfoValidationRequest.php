<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoValidationRequest extends AbstractMailWithLink
{
    private $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function subject()
    {
        return sprintf(_("Validation request from %s : '%s'", $this->emitter->name(), $this->title));
    }

    public function message()
    {
        return $this->message;
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
