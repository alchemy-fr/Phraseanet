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

class MailSuccessEmailUpdate extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('Update of your email address on %application%', ['%application%' => $this->getPhraseanetTitle()], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return sprintf("%s\n%s\n%s",
            $this->app->trans('Dear %user%,', ['%user%' => $this->receiver->getName()], 'messages', $this->getLocale()),
            $this->app->trans('Your contact email address has been updated', [], 'messages', $this->getLocale()),
            $this->message
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->getPhraseanetTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app->url('root');
    }
}
