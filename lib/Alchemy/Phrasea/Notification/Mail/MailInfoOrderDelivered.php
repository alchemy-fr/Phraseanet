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

class MailInfoOrderDelivered extends AbstractMail
{
    /** @var Basket */
    private $basket;
    /** @var \User_Adapter */
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
     * @param \User_Adapter $deliverer
     */
    public function setDeliverer(\User_Adapter $deliverer)
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

        return sprintf(
            _('push::mail:: Reception de votre commande %s'), $this->basket->getName()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->deliverer) {
            throw new LogicException('You must set a deliverer before calling getMessage');
        }

        return sprintf(
            _('%s vous a delivre votre commande, consultez la en ligne a l\'adresse suivante'),
            $this->deliverer->get_display_name()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return _('See my order');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        if (!$this->basket) {
            throw new LogicException('You must set a basket before calling getSubject');
        }

        return $this->app['url_generator']->generate('lightbox_compare', array('ssel_id' => $this->basket->getId()), true);
    }
}
