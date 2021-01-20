<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoReminderFeedback extends AbstractMailWithLink
{
    /** @var string */
    private $title;

    /**
     * Sets the title of the validation to remind
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getSubject');
        }

        return $this->app->trans("Reminder : '%title%'", ['%title%' => $this->title]);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->message;
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
        return $this->url;
    }
}
