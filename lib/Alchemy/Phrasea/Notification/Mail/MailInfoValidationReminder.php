<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
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
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getSubject');
        }

        return $this->app->trans("Reminder : validate '%title%'", ['%title%' => $this->title]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('Il ne vous reste plus que %quantity% jours pour terminer votre validation', [
            '%quantity%' => $this->app['conf']->get(['registry', 'actions', 'validation-reminder-days'])
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Start validation');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
