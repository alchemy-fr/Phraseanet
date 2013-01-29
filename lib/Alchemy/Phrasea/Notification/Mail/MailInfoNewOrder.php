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

class MailInfoNewOrder extends AbstractMail
{
    /** @var \User_Adapter */
    private $user;

    /**
     * Set the user that initiates the order
     *
     * @param \User_Adapter $user
     */
    public function setUser(\User_Adapter $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return sprintf(
            _('admin::register: Nouvelle commande sur %s'),
            $this->getPhraseanetTitle()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->user instanceof \User_Adapter) {
            throw new LogicException('You must set a user before calling getMessage()');
        }

        return sprintf(_('%s has ordered documents'),$this->user->get_display_name());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return sprintf(_('See order on %s'), $this->getPhraseanetTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app['url_generator']->generate('prod', array(), true);
    }
}
