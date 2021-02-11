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

class MailSuccessAccountDelete extends AbstractMail
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('Delete account successfull', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('Your phraseanet account on %urlInstance% has been deleted!', ['%urlInstance%' => '<a href="'.$this->getPhraseanetURL().'">'.$this->getPhraseanetURL().'</a>'], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
    }
}
