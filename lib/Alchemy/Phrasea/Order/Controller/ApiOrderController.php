<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order\Controller;

use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Order\OrderElementTransformer;
use Alchemy\Phrasea\Order\OrderElementViewModel;
use Alchemy\Phrasea\Order\OrderFiller;
use Alchemy\Phrasea\Order\OrderTransformer;
use Alchemy\Phrasea\Order\OrderViewModel;
use Alchemy\Phrasea\Record\RecordReference;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Assert\Assertion;
use Doctrine\Common\Collections\ArrayCollection;
use League\Fractal\Manager;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiOrderController extends BaseOrderController
{
    use JsonBodyAware;

    public function createAction(Request $request)
    {
        $data = $this->decodeJsonBody($request, 'orders.json#/definitions/order_request');

        $availableRecords = $this->toRequestedRecords($data->data->records);
        $records = $this->filterOrderableRecords($availableRecords);

        $recordRequest = new RecordsRequest($records, new ArrayCollection($availableRecords), null, RecordsRequest::FLATTEN_YES);

        $filler = new OrderFiller($this->app['repo.collection-references'], $this->app['orm.em']);

        $filler->assertAllRecordsHaveOrderMaster($recordRequest);

        $order = new Order();
        $order->setUser($this->getAuthenticatedUser());
        $order->setDeadline(new \DateTime($data->data->deadline, new \DateTimeZone('UTC')));
        $order->setOrderUsage($data->data->usage);

        $filler->fillOrder($order, $recordRequest);

        $this->dispatch(PhraseaEvents::ORDER_CREATE, new OrderEvent($order));

        $resource = new Item($order, $this->getOrderTransformer());

        return $this->returnResourceResponse($request, ['elements'], $resource);
    }

    public function indexAction(Request $request)
    {
        $page = max((int) $request->get('page', '1'), 1);
        $perPage = min(max((int)$request->get('per_page', '10'), 1), 100);
        $fractal = $this->parseIncludes($request->get('includes', []));

        $routeGenerator = function ($page) use ($perPage) {
            return $this->app->path('api_v2_orders_index', [
                'page' => $page,
                'per_page' => $perPage,
            ]);
        };

        $builder = $this->app['repo.orders']->createQueryBuilder('o');
        $builder
            ->where($builder->expr()->eq('o.user', $this->getAuthenticatedUser()->getId()))
        ;

        if (in_array('elements', $fractal->getRequestedIncludes(), false)) {
            $builder
                ->addSelect('e')
                ->leftJoin('o.elements', 'e')
            ;
        }

        $collection = $this->buildOrderViewModels($builder->getQuery()->getResult());
        $this->fillViewModels($collection, $fractal->getRequestedIncludes());

        $resource = new Collection($collection, $this->getOrderTransformer());

        $pager = new Pagerfanta(new DoctrineORMAdapter($builder, false));
        $pager->setCurrentPage($page);
        $pager->setMaxPerPage($perPage);

        $resource->setPaginator(new PagerfantaPaginatorAdapter($pager, $routeGenerator));

        return $this->returnResourceResponse($request, $fractal, $resource);
    }

    /**
     * @param Request $request
     * @param int $orderId
     * @return Response
     */
    public function showAction(Request $request, $orderId)
    {
        $order = $this->findOr404($orderId);

        $fractal = $this->parseIncludes($request->get('includes', []));

        if ($order->getUser()->getId() !== $this->getAuthenticatedUser()->getId()) {
            throw new AccessDeniedHttpException(sprintf('Cannot access order "%d"', $order->getId()));
        }

        $model = $this->buildOrderViewModel($order);

        $resource = new Item($model, $this->getOrderTransformer());

        if (in_array('elements.resource_links', $fractal->getRequestedIncludes(), false)) {
            $this->fillViewModels([$resource]);
        }

        return $this->returnResourceResponse($request, $fractal, $resource);
    }

    public function acceptElementsAction(Request $request, $orderId)
    {
        $elementIds = $this->fetchElementIdsFromRequest($request);

        $elements = $this->doAcceptElements($orderId, $elementIds, $this->getAuthenticatedUser());

        $resource = new Collection($elements, function (BasketElement $element) {
            return [
                'id' => $element->getId(),
                'created' => $element->getCreated(),
                'databox_id' => $element->getSbasId(),
                'record_id' => $element->getRecordId(),
                'index' => $element->getOrd(),
            ];
        });

        return $this->returnResourceResponse($request, [], $resource);
    }

    public function denyElementsAction(Request $request, $orderId)
    {
        $elementIds = $this->fetchElementIdsFromRequest($request);

        $this->doDenyElements($orderId, $elementIds, $this->getAuthenticatedUser());

        return Result::create($request, [])->createResponse();
    }

    /**
     * @param array $records
     * @return \record_adapter[]
     */
    private function toRequestedRecords(array $records)
    {
        $requestedRecords = [];

        foreach ($records as $item) {
            $requestedRecords[] = [
                'databox_id' => $item->databox_id,
                'record_id'  => $item->record_id,
            ];
        }

        return RecordReferenceCollection::fromArrayOfArray($requestedRecords)->toRecords($this->getApplicationBox());
    }

    /**
     * @param \record_adapter[] $records
     * @return \record_adapter[]
     */
    private function filterOrderableRecords(array $records)
    {
        $acl = $this->getAclForUser();

        $filtered = [];

        foreach ($records as $index => $record) {
            if ($acl->has_right_on_base($record->getBaseId(), 'cancmd')) {
                $filtered[$index] = $record;
            }
        }

        return $filtered;
    }

    /**
     * @return OrderTransformer
     */
    private function getOrderTransformer()
    {
        return new OrderTransformer(new OrderElementTransformer($this->app['media_accessor.subdef_url_generator']));
    }

    /**
     * @param string|array $includes
     * @return Manager
     */
    private function parseIncludes($includes)
    {
        $fractal = new Manager();

        $fractal->parseIncludes($includes ?: []);

        return $fractal;
    }

    /**
     * @param Request $request
     * @param string|array|Manager $includes
     * @param ResourceInterface $resource
     * @return Response
     */
    private function returnResourceResponse(Request $request, $includes, ResourceInterface $resource)
    {
        $fractal = $includes instanceof Manager ? $includes : $this->parseIncludes($includes);

        return Result::create($request, $fractal->createData($resource)->toArray())->createResponse();
    }

    /**
     * @param Request $request
     * @return array
     */
    private function fetchElementIdsFromRequest(Request $request)
    {
        $data = $this->decodeJsonBody($request, 'orders.json#/definitions/order_element_collection');

        $elementIds = [];

        foreach ($data as $elementId) {
            $elementIds[] = $elementId->id;
        }

        return $elementIds;
    }

    /**
     * @param OrderViewModel[] $models
     * @param array $includes
     * @return void
     */
    private function fillViewModels(array $models, array $includes)
    {
        if (!in_array('elements', $includes, true)) {
            return;
        }

        $elements = $this->gatherElements($models);

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

        $this->createOrderElementViewModels($models, $elements, $records);

        if (!in_array('elements.resource_links', $includes, true)) {
            return;
        }

        // Load all records
        $records->toRecords($this->getApplicationBox());

        // Load all subdefs
        $subdefs = $this->app['service.media_subdef']->findSubdefsFromRecordReferenceCollection($records);
        \media_Permalink_Adapter::getMany($this->app, $subdefs);

        $orderableSubdefs = [];

        foreach ($subdefs as $subdef) {
            $databoxId = $subdef->get_sbas_id();
            $recordId = $subdef->get_record_id();

            if (!isset($orderableSubdefs[$databoxId][$recordId])) {
                $orderableSubdefs[$databoxId][$recordId] = [];
            }

            $orderableSubdefs[$databoxId][$recordId][] = $subdef;
        }

        foreach ($models as $model) {
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
     * @param Order[] $orders
     * @return OrderViewModel[]
     */
    private function buildOrderViewModels(array $orders)
    {
        Assertion::allIsInstanceOf($orders, Order::class);

        return array_map(function (Order $order) {
            return new OrderViewModel($order);
        }, $orders);
    }

    private function buildOrderViewModel(Order $order)
    {
        return new OrderViewModel($order);
    }

    /**
     * @param OrderViewModel[] $models
     * @return OrderElement[][]
     */
    private function gatherElements(array $models)
    {
        Assertion::allIsInstanceOf($models, OrderViewModel::class);

        $elements = [];

        foreach ($models as $index => $model) {
            $elements[$index] = $model->getOrder()->getElements()->toArray();
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

        foreach ($this->app['repo.collection-references']->findMany($baseIds) as $collectionReference) {
            $collectionToDataboxMap[$collectionReference->getBaseId()] = $collectionReference->getDataboxId();
        }

        return $collectionToDataboxMap;
    }

    /**
     * @param OrderViewModel[] $orderViewModels
     * @param OrderElement[][] $elements
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return void
     */
    private function createOrderElementViewModels(array $orderViewModels, $elements, $records)
    {
        $user = $this->getAuthenticatedUser();

        foreach ($orderViewModels as $index => $model) {
            $models = [];

            /** @var OrderElement $element */
            foreach ($elements[$index] as $elementIndex => $element) {
                if (isset($records[$element->getId()])) {
                    $models[$elementIndex] = new OrderElementViewModel($element, $records[$element->getId()], $user);
                }
            }

            $model->setViewElements($models);
        }
    }
}
