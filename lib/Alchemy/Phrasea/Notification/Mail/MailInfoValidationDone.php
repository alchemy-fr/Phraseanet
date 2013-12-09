<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoValidationDone extends AbstractMailWithLink
{
    /** @var string */
    private $title;
    /** @var \User_Adapter */
    private $user;

    /**
     * Sets the title of the validation
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets the user that finished validation
     *
     * @param \User_Adapter $user
     */
    public function setUser(\User_Adapter $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->user) {
            throw new LogicException('You must set an user before calling getSubject');
        }
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getSubject');
        }

        return $this->app->trans('push::mail:: Rapport de validation de %user% pour %title%', [
            '%user%'  => $this->user->get_display_name(),
            '%title%' => $this->title,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->user) {
            throw new LogicException('You must set an user before calling getMessage');
        }

        return $this->app->trans('%user% has just sent its validation report, you can now see it', [
            '%user%' => $this->user->get_display_name(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('See validation results');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
