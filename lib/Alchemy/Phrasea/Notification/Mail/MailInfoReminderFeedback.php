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

        return $this->app->trans("Manual feedback Reminder : '%title%'", ['%title%' => $this->title], 'messages', $this->getLocale());
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
        return $this->app->trans('Start validation', [], 'messages', $this->getLocale());
    }

    /**
     * @inheritDoc
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
