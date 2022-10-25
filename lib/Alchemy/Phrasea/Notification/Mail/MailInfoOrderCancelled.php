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
use Alchemy\Phrasea\Model\Entities\User;

class MailInfoOrderCancelled extends AbstractMail
{
    /** @var User */
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
        return $this->app->trans('push::mail:: Refus d\'elements de votre commande', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->deliverer instanceof User) {
            throw new LogicException('You must set a deliverer before calling getMessage()');
        }
        if (null === $this->quantity) {
            throw new LogicException('You must set a deliverer before calling getMessage()');
        }

        return $this->app->trans('%user% a refuse %quantity% elements de votre commande', [
            '%user%' => $this->deliverer->getDisplayName(),
            '%quantity%' => $this->quantity,
        ], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
    }
}
