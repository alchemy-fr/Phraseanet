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
        return sprintf(_('Your account on %s'), $this->getPhraseanetTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->login) {
            throw new LogicException('You must set a login before calling getMessage');
        }

        return sprintf(_('Your account with the login %s as been created'), $this->login)
            . "\n"
            . _('You now have to set up your pasword');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Setup my password');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
