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

class ElementsAdded extends Event
{
    /** @var Basket */
    private $basket;
    /** @var array */
    private $requestedRecords;
    /** @var BasketElement[] */
    private $elementsAdded;
    /** @var array */
    private $errors;

    /**
     * @param Basket          $basket
     * @param array           $requestedRecords
     * @param BasketElement[] $elementsAdded
     * @param array           $errors
     */
    public function __construct(Basket $basket, $requestedRecords, $elementsAdded, array $errors = [])
    {
        $this->basket = $basket;
        $this->requestedRecords = $requestedRecords;
        $this->elementsAdded = $elementsAdded;
        $this->errors = $errors;
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
    public function getElementsAdded()
    {
        return $this->elementsAdded;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
