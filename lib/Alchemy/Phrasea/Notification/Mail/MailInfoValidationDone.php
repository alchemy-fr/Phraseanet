<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoValidationDone extends AbstractMailWithLink
{
    private $title;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function subject()
    {
        return sprintf(
            _('push::mail:: Rapport de validation de %1$s pour %2$s'),
            $this->emitter->name(),
            $this->title
        );
    }

    public function message()
    {
        return sprintf(
            _('%s has just sent its validation report, you can now see it'),
            $this->emitter->name()
        );
    }

    public function buttonText()
    {
        return _('See validation results');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
