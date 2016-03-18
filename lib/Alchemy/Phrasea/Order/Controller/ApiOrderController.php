<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order\Controller;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\OrderElementRepository;
use Alchemy\Phrasea\Order\OrderElementTransformer;
use Alchemy\Phrasea\Order\OrderFiller;
use Alchemy\Phrasea\Order\OrderTransformer;
use Alchemy\Phrasea\Order\OrderValidator;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Assert\Assertion;
use Assert\InvalidArgumentException;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiOrderController extends Controller
{
    use DispatcherAware;
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
        $perPage = min(max((int)$request->get('per_page', '10'), 10), 100);
        $includes = $request->get('includes', []);

        $routeGenerator = function ($page) use ($perPage) {
            return $this->app->path('api_v2_orders_index', [
                'page' => $page,
                'per_page' => $perPage,
            ]);
        };


        $builder = $this->app['repo.orders']->createQueryBuilder('o');
        $builder
            ->where($builder->expr()->eq('o.user', $this->getAuthenticatedUser()->getId()))
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
        ;

        $resource = new Collection($builder->getQuery()->getResult(), $this->getOrderTransformer());

        $pager = new Pagerfanta(new DoctrineORMAdapter($builder, false));
        $pager->setCurrentPage($page);
        $paginator = new PagerfantaPaginatorAdapter($pager, $routeGenerator);
        $resource->setPaginator($paginator);

        return $this->returnResourceResponse($request, $includes, $resource);
    }

    /**
     * @param Request $request
     * @param int $orderId
     * @return Response
     */
    public function showAction(Request $request, $orderId)
    {
        $order = $this->findOr404($orderId);

        $includes = $request->get('includes', []);

        if ($order->getUser()->getId() !== $this->getAuthenticatedUser()->getId()) {
            throw new AccessDeniedHttpException(sprintf('Cannot access order "%d"', $order->getId()));
        }

        $resource = new Item($order, $this->getOrderTransformer());

        return $this->returnResourceResponse($request, $includes, $resource);
    }

    public function acceptElementsAction(Request $request, $orderId)
    {
        $data = $this->decodeJsonBody($request, 'orders.json#/definitions/order_element_collection');
        $acceptor = $this->getAuthenticatedUser();



        return Result::create($request, [])->createResponse();
    }

    public function denyElementsAction(Request $request, $orderId)
    {
        $order = $this->findOr404($orderId);

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
        return new OrderTransformer(new OrderElementTransformer($this->app));
    }

    /**
     * @param Request $request
     * @param string|array $includes
     * @param ResourceInterface $resource
     * @return Response
     */
    private function returnResourceResponse(Request $request, $includes, ResourceInterface $resource)
    {
        $fractal = new Manager();
        $fractal->parseIncludes($includes);

        return Result::create($request, $fractal->createData($resource)->toArray())->createResponse();
    }

    /**
     * @param int $orderId
     * @return Order
     */
    private function findOr404($orderId)
    {
        try {
            Assertion::integerish($orderId);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $order = $this->app['repo.orders']->find((int)$orderId);

        if (!$order instanceof Order) {
            throw new NotFoundHttpException(sprintf('Order "%d" was not found', (int)$orderId));
        }

        return $order;
    }

    /**
     * @param int $orderId
     * @param array<object> $elementIds
     * @param User $acceptor
     * @return OrderElement[]
     */
    private function findRequestedElements($orderId, array $elementIds, User $acceptor)
    {
        $ids = [];

        foreach ($elementIds as $elementId) {
            if (!isset($elementId->id)) {
                throw new BadRequestHttpException('Invalid element id collection given');
            }

            $ids[] = $elementId->id;
        }

        $elements = $this->getOrderElementRepository()->findBy([
            'id' => $ids,
            'order' => $orderId,
        ]);

        if (count($elements) !== count($elementIds)) {
            throw new NotFoundHttpException(sprintf('At least one requested element does not exists or does not belong to order "%s"', $orderId));
        }

        if (!$this->getOrderValidator()->isGrantedValidation($acceptor, $elements)) {
            throw new AccessDeniedHttpException('At least one element is in a collection you have no access to.');
        }

        return $elements;
    }

    /**
     * @return OrderElementRepository
     */
    private function getOrderElementRepository()
    {
        return $this->app['repo.order-elements'];
    }

    /**
     * @return OrderValidator
     */
    private function getOrderValidator()
    {
        return $this->app['validator.order'];
    }
}
