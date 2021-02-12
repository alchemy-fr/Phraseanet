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

class MailInfoRecordQuarantined extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('A document has been quarantined', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('A file has been thrown to the quarantine.', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Access quarantine', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app->url('root');
    }
}
