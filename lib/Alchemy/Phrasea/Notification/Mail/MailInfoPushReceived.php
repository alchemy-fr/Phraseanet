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

use Entities\Basket;
use Alchemy\Phrasea\Exception\LogicException;

class MailInfoPushReceived extends AbstractMailWithLink
{
    private $basket;
    private $pusher;
    private $quantity;

    public function setQuantity($quantity)
    {
        $this->quantity = (int) $quantity;
    }

    public function setBasket(Basket $basket)
    {
        $this->basket = $basket;
    }

    public function setPusher(\User_Adapter $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->basket) {
            throw new LogicException('You must set a basket before calling getSubject');
        }

        return sprintf(_('Reception of %s'), $this->basket->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->pusher) {
            throw new LogicException('You must set a basket before calling getMessage');
        }
        if (!$this->quantity) {
            throw new LogicException('You must set quantity before calling getMessage');
        }

        return
            sprintf(_('You just received a push containing %s documents from %s'), $this->quantity, $this->pusher->get_display_name())
            . "\n" . $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('Watch it online');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
