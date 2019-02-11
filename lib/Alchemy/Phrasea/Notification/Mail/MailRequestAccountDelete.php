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

class MailRequestAccountDelete extends AbstractMailWithLink
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('Delete account confirmation');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('In Phraseanet,your baskets and your api application will be deleted with your account.Click the button to confirm deletion');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Delete my account');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }

}