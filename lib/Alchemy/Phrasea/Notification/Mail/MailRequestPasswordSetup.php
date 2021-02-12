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

class MailRequestPasswordSetup extends AbstractMailWithLink
{
    /** @var string */
    private $login;

    /**
     * Sets the login related to the password renewal
     *
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('Your account on %application%', ['%application%' => $this->getPhraseanetTitle()], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->login) {
            throw new LogicException('You must set a login before calling getMessage');
        }

        return $this->app->trans('Your account with the login %login% as been created', ['%login%' => $this->login], 'messages', $this->getLocale())
            . "\n"
            . $this->app->trans('You now have to set up your pasword', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Setup my password', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
