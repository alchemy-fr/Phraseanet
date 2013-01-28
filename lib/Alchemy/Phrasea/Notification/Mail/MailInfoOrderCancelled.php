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
    private $deliverer;
    private $quantity;

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function setDeliverer(\User_Adapter $deliverer)
    {
        $this->deliverer = $deliverer;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return _('push::mail:: Refus d\'elements de votre commande');
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

        return sprintf(
            _('%s a refuse %d elements de votre commande'),
            $this->deliverer->get_display_name(),
            $this->quantity
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
        return $this->app['url_generator']->generate('prod', array(), true);
    }
}
