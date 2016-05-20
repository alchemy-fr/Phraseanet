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

use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'elements',
    ];

    /**
     * @var OrderElementTransformer
     */
    private $elementTransformer;

    public function __construct(OrderElementTransformer $elementTransformer)
    {
        $this->elementTransformer = $elementTransformer;
    }

    public function transform(OrderView $view)
    {
        $order = $view->getOrder();

        $data = [
            'id' => (int)$order->getId(),
            'owner_id' => (int)$order->getUser()->getId(),
            'created' => $order->getCreatedOn()->format(DATE_ATOM),
            'usage' => $order->getOrderUsage(),
            'status' => 0 === $order->getTodo() ? 'finished' : 'pending',
        ];

        if ($view->getArchiveUrl()) {
            $data['archive_url'] = $view->getArchiveUrl();
        }

        if ($order->getDeadline()) {
            $data['deadline'] = $order->getDeadline()->format(DATE_ATOM);
        }

        return $data;
    }

    public function includeElements(OrderView $order)
    {
        $elements = $order->getElements();

        return $this->collection($elements, $this->elementTransformer);
    }
}
