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

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoUserRegistered extends AbstractMail
{
    private $registeredUser;

    public function setRegisteredUser($registeredUser)
    {
        $this->registeredUser = $registeredUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return sprintf(
            _('admin::register: demande d\'inscription sur %s'), $this->getPhraseanetTitle()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->registeredUser) {
            throw new LogicException('You must set a user before calling getMessage');
        }

        return _('admin::register: un utilisateur a fait une demande d\'inscription')
        . "\n\n" .  sprintf('%s %s',$this->registeredUser->get_firstname(),  $this->registeredUser->get_lastname())
        . "\n\n" .  sprintf('%s %s',$this->registeredUser->get_job(),  $this->registeredUser->get_company());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Process the registration');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app['url_generator']->generate('admin', array('section' => 'registrations'), true);
    }
}
