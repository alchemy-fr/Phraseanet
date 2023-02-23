<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoReminderFeedback extends AbstractMailWithLink
{
    /** @var string */
    private $title;

    private $isFeedback = true;

    /**
     * Sets the title of the validation to remind
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setFeedback(bool $isFeedback)
    {
        $this->isFeedback = !! $isFeedback;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getSubject');
        }

        if ($this->isFeedback) {
            return $this->app->trans("Manual feedback Reminder : '%title%'", ['%title%' => $this->title], 'messages', $this->getLocale());
        } else {
            return $this->app->trans("Manual email share: '%title%'", ['%title%' => $this->title], 'messages', $this->getLocale());
        }
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
        if ($this->isFeedback) {
            return $this->app->trans('Start validation', [], 'messages', $this->getLocale());
        } else {
            return $this->app->trans('mail::share Open with Lightbox', [], 'messages', $this->getLocale());
        }
    }

    /**
     * @inheritDoc
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
