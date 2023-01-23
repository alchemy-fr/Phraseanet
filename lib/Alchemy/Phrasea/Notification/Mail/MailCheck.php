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

class MailCheck extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans("mail:: test email sended", [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans("mail:: test sended from %application%", ['%application%' => $this->getPhraseanetTitle()], 'messages', $this->getLocale());
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
