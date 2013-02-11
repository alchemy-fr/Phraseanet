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

class MailInfoRecordQuarantined extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return _('A document has been quarantined');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return _('A file has been thrown to the quarantine.');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Access quarantine');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app['url_generator']->generate('root', array(), true);
    }
}
