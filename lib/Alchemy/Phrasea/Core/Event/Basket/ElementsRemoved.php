<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Core\Event\Basket;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Symfony\Component\EventDispatcher\Event;

class ElementsRemoved extends Event
{
    /** @var Basket */
    private $basket;
    /** @var array */
    private $requestedRecords;
    /** @var BasketElement[] */
    private $elementsRemoved;

    /**
     * @param Basket          $basket
     * @param array           $requestedRecords
     * @param BasketElement[] $elementsRemoved
     */
    public function __construct(Basket $basket, $requestedRecords, $elementsRemoved)
    {
        $this->basket = $basket;
        $this->requestedRecords = $requestedRecords;
        $this->elementsRemoved = $elementsRemoved;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @return array
     */
    public function getRequestedRecords()
    {
        return $this->requestedRecords;
    }

    /**
     * @return BasketElement[]
     */
    public function getElementsRemoved()
    {
        return $this->elementsRemoved;
    }
}
