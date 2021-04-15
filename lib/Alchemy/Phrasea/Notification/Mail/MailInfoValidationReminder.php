<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoValidationReminder extends AbstractMailWithLink
{
    /** @var string */
    private $title;

    /** @var string */
    private $timeLeft;

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
     *  Sets time left before the validation expires
     * @param $timeLeft
     */
    public function setTimeLeft($timeLeft)
    {
        $this->timeLeft = $timeLeft;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getSubject');
        }

        return $this->app->trans("Reminder : validate '%title%'", ['%title%' => $this->title], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('Il ne vous reste plus que %timeLeft% pour terminer votre validation', [
            '%timeLeft%' => isset($this->timeLeft)? $this->timeLeft : ''
        ], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Start validation', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
