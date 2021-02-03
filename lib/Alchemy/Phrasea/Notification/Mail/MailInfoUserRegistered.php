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

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Model\Entities\User;

class MailInfoUserRegistered extends AbstractMail
{
    /** @var User */
    private $registeredUser;

    /**
     * Sets the user that just registered
     *
     * @param User $registeredUser
     */
    public function setRegisteredUser(User $registeredUser)
    {
        $this->registeredUser = $registeredUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('admin::register: demande d\'inscription sur %application%', ['%application%' => $this->getPhraseanetTitle()], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->registeredUser) {
            throw new LogicException('You must set a user before calling getMessage');
        }

        return $this->app->trans('admin::register: un utilisateur a fait une demande d\'inscription', [], 'messages', $this->getLocale())
        . "\n\n" .  sprintf('%s %s',$this->registeredUser->getFirstName(),  $this->registeredUser->getLastName())
        . "\n\n" .  sprintf('%s %s',$this->registeredUser->getJob(),  $this->registeredUser->getCompany());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Process the registration', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app->url('admin', ['section' => 'registrations']);
    }
}
