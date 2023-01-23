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

class MailRequestPasswordUpdate extends AbstractMailWithLink
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
        return $this->app->trans('login:: Forgot your password', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->login) {
            throw new LogicException('You must set a login before calling getMessage');
        }

        return $this->app->trans('mail:: Password renewal for login', [], 'messages', $this->getLocale())
        . "\n\n <strong>" . $this->login . "</strong> \n\n"
        .  $this->app->trans('login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Renew password', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
