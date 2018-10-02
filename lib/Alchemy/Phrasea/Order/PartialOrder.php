<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Assert\Assertion;

class PartialOrder
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var OrderElement[]
     */
    private $elements;

    /**
     * @param Order $order
     * @param OrderElement[] $elements
     */
    public function __construct(Order $order, $elements)
    {
        Assertion::allIsInstanceOf($elements, OrderElement::class);

        $this->order = $order;

        $this->elements = [];

        foreach ($elements as $element) {
            if (null === $element->getOrder() || $element->getOrder()->getId() !== $order->getId()) {
                throw new \InvalidArgumentException('Elements should belong to same order');
            }

            $this->elements[$element->getId()] = $element;
        }
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return OrderElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    public function getBaseIds()
    {
        $baseIds = [];

        foreach ($this->elements as $element) {
            $baseIds[$element->getBaseId()] = true;
        }

        return array_keys($baseIds);
    }
}
