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

class MailTest extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('mail:: test d\'envoi d\'email');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return sprintf("%s\n%s", $this->app->trans('Ce mail est un test d\'envoi de mail depuis %application%', ['%application%' => $this->getPhraseanetTitle()]), $this->message);
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
