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

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Model\Entities\User;

class MailInfoOrderDelivered extends AbstractMailWithLink
{
    /** @var Basket */
    private $basket;
    /** @var User */
    private $deliverer;

    /**
     * Sets the basket where the order has been delivered
     *
     * @param Basket $basket
     */
    public function setBasket(Basket $basket)
    {
        $this->basket = $basket;
    }

    /**
     * Sets the user that delivers the order
     *
     * @param User $deliverer
     */
    public function setDeliverer(User $deliverer)
    {
        $this->deliverer = $deliverer;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->basket) {
            throw new LogicException('You must set a basket before calling getSubject');
        }

        return $this->app->trans('push::mail:: Reception de votre commande %title%', ['%title%' => $this->basket->getName()], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->deliverer) {
            throw new LogicException('You must set a deliverer before calling getMessage');
        }

        return $this->app->trans('%user% vous a delivre votre commande, consultez la en ligne a l\'adresse suivante', ['%user%' => $this->deliverer->getDisplayName()], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('See my order', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
