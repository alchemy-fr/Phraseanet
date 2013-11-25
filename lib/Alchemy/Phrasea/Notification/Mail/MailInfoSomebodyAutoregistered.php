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

class MailInfoSomebodyAutoregistered extends AbstractMailWithLink
{
    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('admin::register: Inscription automatique sur %application%', array('%application%' => $this->getPhraseanetTitle()));
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->app->trans('admin::register: un utilisateur s\'est inscrit')."\n\n".$this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Update the account');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app->url('admin', ['section' => 'users']);
    }
}
