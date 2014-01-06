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

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Exception\LogicException;

class MailInfoPushReceived extends AbstractMailWithLink
{
    /** @var Basket */
    private $basket;
    /** @var \User_Adapter */
    private $pusher;

    /**
     * Sets the basket
     *
     * @param Basket $basket
     */
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

        return $this->app->trans('Reception of %basket_name%', ['%basket_name%' => $this->basket->getName()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->pusher) {
            throw new LogicException('You must set a basket before calling getMessage');
        }
        if (!$this->basket) {
            throw new LogicException('You must set a basket before calling getMessage');
        }

        return
            $this->app->trans('You just received a push containing %quantity% documents from %user%', ['%quantity%' => count($this->basket->getElements()), '%user%' => $this->pusher->get_display_name()])
            . "\n" . $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Watch it online');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
