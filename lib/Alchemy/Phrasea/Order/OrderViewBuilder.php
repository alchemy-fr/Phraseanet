<?php

/*
 * This file is part of alchemy/pipeline-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefService;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Assert\Assertion;

class OrderViewBuilder
{

    /**
     * @var Application
     */
    private $application;

    /**
     * @var \appbox
     */
    private $applicationBox;

    /**
     * @var MediaSubdefService
     */
    private $mediaSubdefService;

    /**
     * @param Application $application
     * @param \appbox $appbox
     * @param MediaSubdefService $subdefService
     */
    public function __construct(Application $application, \appbox $appbox, MediaSubdefService $subdefService)
    {
        $this->application = $application;
        $this->applicationBox = $appbox;
        $this->mediaSubdefService = $subdefService;
    }

    public function buildView(Order $order, array $includes)
    {
        $view = new OrderView($order);

        $this->fillViews([$view], $includes);

        return $view;
    }

    /**
     * @param Order[] $orders
     * @param string[] $includes
     * @return OrderView[]
     */
    public function buildViews(array $orders, array $includes)
    {
        Assertion::allIsInstanceOf($orders, Order::class);

        $views = array_map(function (Order $order) {
            return new OrderView($order);
        }, $orders);

        $this->fillViews($views, $includes);

        return $views;
    }

    /**
     * @param OrderView[] $views
     * @param array $includes
     * @return void
     */
    private function fillViews(array $views, array $includes)
    {
        array_walk($views, function (OrderView $view) {
            // Archive is only available when a Basket is associated with the order (at least one element was accepted)
            if (null === $basket = $view->getOrder()->getBasket()) {
                return;
            }

            if ($basket->getElements()->isEmpty()) {
                return;
            }

            $view->setArchiveUrl($this->application->url('api_v2_orders_archive', [
                'orderId' => $view->getOrder()->getId(),
            ]));
        });

        if (!in_array('elements', $includes, true)) {
            return;
        }

        $elements = $this->gatherElements($views);

        $allElements = $elements ? call_user_func_array('array_merge', $elements) : [];
        $allElements = array_combine(
            array_map(function (OrderElement $element) {
                return $element->getId();
            }, $allElements),
            $allElements
        );

        if (!$allElements) {
            return;
        }

        $collectionToDataboxMap = $this->mapBaseIdToDataboxId($allElements);

        $records = RecordReferenceCollection::fromListExtractor(
            $allElements,
            function (OrderElement $element) use ($collectionToDataboxMap) {
                return isset($collectionToDataboxMap[$element->getBaseId()])
                    ? [$collectionToDataboxMap[$element->getBaseId()], $element->getRecordId()]
                    : null;
            },
            function (array $data) {
                list ($databoxId, $recordId) = $data;

                return RecordReference::createFromDataboxIdAndRecordId($databoxId, $recordId);
            }
        );

        $this->createOrderElementViews($views, $elements, $records);

        if (!in_array('elements.resource_links', $includes, true)) {
            return;
        }

        // Load all records
        $records->toRecords($this->applicationBox);

        // Load all subdefs
        $subdefs = $this->mediaSubdefService->findSubdefsFromRecordReferenceCollection($records);
        \media_Permalink_Adapter::getMany($this->application, $subdefs);

        $orderableSubdefs = [];

        foreach ($subdefs as $subdef) {
            $databoxId = $subdef->get_sbas_id();
            $recordId = $subdef->get_record_id();

            if (!isset($orderableSubdefs[$databoxId][$recordId])) {
                $orderableSubdefs[$databoxId][$recordId] = [];
            }

            $orderableSubdefs[$databoxId][$recordId][] = $subdef;
        }

        foreach ($views as $model) {
            foreach ($model->getElements() as $element) {
                $databoxId = $collectionToDataboxMap[$element->getElement()->getBaseId()];
                $recordId = $element->getElement()->getRecordId();

                if (isset($orderableSubdefs[$databoxId][$recordId])) {
                    $element->setOrderableMediaSubdefs($orderableSubdefs[$databoxId][$recordId]);
                }
            }
        }
    }


    /**
     * @param OrderView[] $orderViews
     * @return OrderElement[][]
     */
    private function gatherElements(array $orderViews)
    {
        Assertion::allIsInstanceOf($orderViews, OrderView::class);

        $elements = [];

        foreach ($orderViews as $index => $orderView) {
            $elements[$index] = $orderView->getOrder()->getElements()->toArray();
        }

        return $elements;
    }

    /**
     * @param OrderElement[] $elements
     * @return array
     */
    private function mapBaseIdToDataboxId(array $elements)
    {
        $baseIds = array_keys(array_reduce($elements, function (array &$baseIds, OrderElement $element) {
            $baseIds[$element->getBaseId()] = true;

            return $baseIds;
        }, []));

        $collectionToDataboxMap = [];

        foreach ($this->application['repo.collection-references']->findMany($baseIds) as $collectionReference) {
            $collectionToDataboxMap[$collectionReference->getBaseId()] = $collectionReference->getDataboxId();
        }

        return $collectionToDataboxMap;
    }

    /**
     * @param OrderView[] $orderViews
     * @param OrderElement[][] $elements
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return void
     */
    private function createOrderElementViews(array $orderViews, $elements, $records)
    {
        $user = $this->application->getAuthenticatedUser();

        foreach ($orderViews as $index => $model) {
            $models = [];

            /** @var OrderElement $element */
            foreach ($elements[$index] as $elementIndex => $element) {
                if (isset($records[$element->getId()])) {
                    $models[$elementIndex] = new OrderElementView($element, $records[$element->getId()], $user);
                }
            }

            $model->setViewElements($models);
        }
    }
}
