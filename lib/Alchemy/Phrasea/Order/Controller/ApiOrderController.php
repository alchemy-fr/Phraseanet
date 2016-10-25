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

use Alchemy\Phrasea\Application\Helper\DelivererAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\ExportEvent;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Order\OrderElementTransformer;
use Alchemy\Phrasea\Order\OrderFiller;
use Alchemy\Phrasea\Order\OrderTransformer;
use Alchemy\Phrasea\Order\OrderViewBuilder;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiOrderController extends BaseOrderController
{
    use DelivererAware;
    use FilesystemAware;
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
        $order->setNotificationMethod(Order::NOTIFY_WEBHOOK);

        $filler->fillOrder($order, $recordRequest);

        $this->dispatch(PhraseaEvents::ORDER_CREATE, new OrderEvent($order));

        $view = $this->getViewBuilder()->buildView($order, []);
        $resource = new Item($view, $this->getOrderTransformer());

        return $this->returnResourceResponse($request, ['elements'], $resource);
    }

    public function indexAction(Request $request)
    {
        $page = max((int) $request->get('page', '1'), 1);
        $perPage = min(max((int)$request->get('per_page', '10'), 1), 100);
        $fractal = $this->buildFractalManager($request->get('includes', []));

        $routeGenerator = function ($page) use ($perPage) {
            return $this->app->path('api_v2_orders_index', [
                'page' => $page,
                'per_page' => $perPage,
            ]);
        };

        $builder = $this->getOrderRepository()->createQueryBuilder('o');
        $builder
            ->where($builder->expr()->eq('o.user', $this->getAuthenticatedUser()->getId()))
        ;

        if (in_array('elements', $fractal->getRequestedIncludes(), false)) {
            $builder
                ->addSelect('e')
                ->leftJoin('o.elements', 'e')
            ;
        }

        $collection = $this->getViewBuilder()->buildViews(
            $builder->getQuery()->getResult(),
            $fractal->getRequestedIncludes()
        );

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

        $fractal = $this->buildFractalManager($request->get('includes', []));

        if (! $this->isOrderAccessible($order)) {
            throw new AccessDeniedHttpException(sprintf('Cannot access order "%d"', $order->getId()));
        }

        $model = $this->getViewBuilder()->buildView($order, $fractal->getRequestedIncludes());
        $resource = new Item($model, $this->getOrderTransformer());

        return $this->returnResourceResponse($request, $fractal, $resource);
    }

    /**
     * @param int $orderId
     * @return Response
     */
    public function getArchiveAction($orderId)
    {
        $order = $this->findOr404($orderId);

        if (! $this->isOrderAccessible($order)) {
            throw new AccessDeniedHttpException(sprintf('Cannot access order "%d"', $order->getId()));
        }

        $basket = $order->getBasket();

        if (null === $basket instanceof Basket) {
            throw new NotFoundHttpException(sprintf('No archive could be downloaded for Order "%d"', $order->getId()));
        }

        $export = new \set_export($this->app, '', $basket->getId());
        $exportName = sprintf('%s/%s.zip', $this->app['tmp.download.path'], $export->getExportName());

        $user = $this->getAuthenticatedUser();

        $subdefs = $this->findDataboxSubdefNames();

        $exportData = $export->prepare_export($user, $this->getFilesystem(), $subdefs, true, true);
        $exportData['export_name'] = $exportName;

        /** @var Token $token */
        $token = $this->app['manipulator.token']->createDownloadToken($user, serialize($exportData));
        $lst = [];

        foreach ($exportData['files'] as $file) {
            $lst[] = $this->getApplicationBox()->get_collection($file['base_id'])->get_databox()->get_sbas_id() . '_' . $file['record_id'];
        }

        $this->dispatch(
            PhraseaEvents::EXPORT_CREATE,
            new ExportEvent($user, $basket->getId(), implode(';', $lst), $subdefs, $exportName)
        );

        set_time_limit(0);
        ignore_user_abort(true);
        $file = \set_export::build_zip($this->app, $token, $exportData, $token->getValue() . '.zip');

        return $this->deliverFile($file, $exportName, DeliverDataInterface::DISPOSITION_INLINE, 'application/zip');
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
    private function buildFractalManager($includes)
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
        $fractal = $includes instanceof Manager ? $includes : $this->buildFractalManager($includes);

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

    private function getViewBuilder()
    {
        return new OrderViewBuilder(
            $this->app,
            $this->getApplicationBox(),
            $this->app['service.media_subdef']
        );
    }

    /**
     * @param $order
     * @return bool
     */
    private function isOrderAccessible(Order $order)
    {
        if ($order->getUser()->getId() === $this->getAuthenticatedUser()->getId()) {
            return true;
        }

        if ($order->getUser()->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function findDataboxSubdefNames()
    {
        $subdefNames = [
            'document' => true,
        ];
        
        foreach ($this->getApplicationBox()->get_databoxes() as $databox) {
            foreach ($databox->get_subdef_structure() as $subdefGroup) {
                /** @var \databox_subdef $subdef */
                foreach ($subdefGroup as $subdef) {
                    $subdefNames[$subdef->get_name()] = true;
                }
            }
        }

        return array_keys($subdefNames);
    }
}
