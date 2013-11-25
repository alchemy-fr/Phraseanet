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

class MailInfoOrderCancelled extends AbstractMail
{
    /** @var \User_Adapter */
    private $deliverer;
    /** @var integer */
    private $quantity;

    /**
     * Sets the quantity that has been denied
     *
     * @param integer $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Sets the user that has denied the some of the order
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
        return $this->app->trans('push::mail:: Refus d\'elements de votre commande');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->deliverer instanceof \User_Adapter) {
            throw new LogicException('You must set a deliverer before calling getMessage()');
        }
        if (null === $this->quantity) {
            throw new LogicException('You must set a deliverer before calling getMessage()');
        }

        return $this->app->trans('%user% a refuse %quantity% elements de votre commande', array(
            '%user%' => $this->deliverer->get_display_name(),
            '%quantity%' => $this->quantity,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('See my order');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->app->url('prod');
    }
}
