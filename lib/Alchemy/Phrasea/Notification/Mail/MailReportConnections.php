<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailReportConnections extends AbstractMail
{

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        return $this->app->trans("mail:: report", [], 'messages', $this->getLocale());
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->app->trans("mail:: report messages", [], 'messages', $this->getLocale());
    }

    /**
     * @inheritDoc
     */
    public function getButtonText()
    {
    }

    /**
     * @inheritDoc
     */
    public function getButtonURL()
    {
    }
}
