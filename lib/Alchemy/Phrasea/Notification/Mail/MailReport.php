<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailReport extends AbstractMail
{
    const MAIL_SKIN = 'report';

    private $reportName;

    public function setReportName($reportName)
    {
        $this->reportName = $reportName;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        if (!$this->reportName) {
            throw new LogicException('You must set the report name');
        }

        return $this->app->trans("mail:: report - %instance% - %reportName%", [
            '%instance%'    => $this->getPhraseanetTitle(),
            '%reportName%'  => $this->reportName
        ],
            'messages', $this->getLocale());
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

    public function getMailSkin()
    {
        return self::MAIL_SKIN;
    }
}
