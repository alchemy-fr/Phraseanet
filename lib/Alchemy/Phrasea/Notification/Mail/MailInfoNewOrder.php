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
use Alchemy\Phrasea\Model\Entities\User;

class MailInfoNewOrder extends AbstractMail
{
    /** @var User */
    private $user;

    /**
     * Set the user that initiates the order
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('admin::register: Nouvelle commande sur %s', ['%application%' => $this->getPhraseanetTitle()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->user instanceof User) {
            throw new LogicException('You must set a user before calling getMessage()');
        }

        return $this->app->trans('%user% has ordered documents', ['%user%' => $this->user->getDisplayName($this->app['translator'])]);
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Review order on %website%', ['%website%' => $this->getPhraseanetTitle()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app->url('prod');
    }
}
