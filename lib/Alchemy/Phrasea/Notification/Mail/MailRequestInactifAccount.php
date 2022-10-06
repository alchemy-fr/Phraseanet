<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailRequestInactifAccount extends AbstractMailWithLink
{

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        return $this->app->trans("mail:: inactif account");
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->app->trans("mail:: your account is inactif and to be deleted!");
    }

    /**
     * @inheritDoc
     */
    public function getButtonText()
    {
        return $this->app->trans("mail:: connect to phraseanet");
    }

    /**
     * @inheritDoc
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
