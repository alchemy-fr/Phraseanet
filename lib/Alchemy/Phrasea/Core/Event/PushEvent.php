<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Model\Entities\Basket;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class PushEvent extends SfEvent
{
    private $basket;
    private $message;
    private $receipt;
    private $url;

    public function __construct(Basket $basket, $message, $url, $receipt)
    {
        $this->basket = $basket;
        $this->message = $message;
        $this->url = $url;
        $this->receipt = (Boolean) $receipt;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
    public function hasReceipt()
    {
        return $this->receipt;
    }
    public function getUrl()
    {
        return $this->url;
    }
}
