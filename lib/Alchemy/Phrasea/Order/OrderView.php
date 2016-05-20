<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Model\Entities\Order;
use Assert\Assertion;

class OrderView
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var string
     */
    private $archiveUrl;

    /**
     * @var OrderElementView[]
     */
    private $viewElements = [];

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @param OrderElementView[] $viewElements
     */
    public function setViewElements($viewElements)
    {
        Assertion::allIsInstanceOf($viewElements, OrderElementView::class);

        $this->viewElements = $viewElements instanceof \Traversable ? iterator_to_array($viewElements) : $viewElements;
    }

    /**
     * @param string $archiveUrl
     */
    public function setArchiveUrl($archiveUrl)
    {
        $this->archiveUrl = (string)$archiveUrl;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getArchiveUrl()
    {
        return $this->archiveUrl;
    }

    /**
     * @return OrderElementView[]
     */
    public function getElements()
    {
        return $this->viewElements;
    }
}
