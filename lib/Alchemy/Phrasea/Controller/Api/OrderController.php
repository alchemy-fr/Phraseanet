<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Order\OrderElementTransformer;
use Alchemy\Phrasea\Order\OrderFiller;
use Alchemy\Phrasea\Order\OrderTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use League\Fractal\Manager;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends Controller
{
    use DispatcherAware;
    use JsonBodyAware;

    public function createAction(Request $request)
    {
        $data = $this->decodeJsonBody($request, 'orders.json#/definitions/order_request');

        $requestedRecords = $this->toRequestedRecordsArray($data->data->records);
        $availableRecords = $this->fetchRecords($requestedRecords);
        $records = $this->filterOrderableRecords($availableRecords);

        $recordRequest = new RecordsRequest($records, new ArrayCollection($availableRecords), null, RecordsRequest::FLATTEN_YES);

        $filler = new OrderFiller($this->app['repo.collection-references'], $this->app['orm.em']);

        $filler->assertAllRecordsHaveOrderMaster($recordRequest);

        $order = new Order();
        $order->setUser($this->getAuthenticatedUser());
        $order->setDeadline(new \DateTime($data->data->deadline, new \DateTimeZone('UTC')));
        $order->setOrderUsage($data->data->usage);

        $filler->fillOrder($order, $recordRequest);

        $transformer = new OrderTransformer(new OrderElementTransformer($this->app));

        $fractal = new Manager();
        $fractal->parseIncludes(['elements']);

        $result = Result::create($request, [
            'order' => $fractal->createData(new Item($order, $transformer))->toArray(),
        ]);

        $this->dispatch(PhraseaEvents::ORDER_CREATE, new OrderEvent($order));

        return $result->createResponse();
    }

    public function indexAction(Request $request)
    {
        $page = max((int) $request->get('page', '1'), 1);
        $perPage = min(max((int)$request->get('per_page', '10'), 10), 100);
        $includes = $request->get('includes', '');

        $offset = ($page - 1) * $perPage;

        $orders = $this->app['repo.orders']->createQueryBuilder('o');
        $orders
            ->where($orders->expr()->eq('o.user', $this->getAuthenticatedUser()->getId()))
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
        ;

        $transformer = new OrderTransformer(new OrderElementTransformer($this->app));

        $routeGenerator = function ($page) use ($perPage) {
            return $this->app->url('api_v2_orders_index', [
                'page' => $page,
                'perPage' => $perPage,
            ]);
        };


        $fractal = new Manager();
        $fractal->parseIncludes($includes);

        $collection = new Collection($orders, $transformer);

        $paginator = new PagerfantaPaginatorAdapter(new Pagerfanta(new DoctrineORMAdapter($orders, false)), $routeGenerator);
        $collection->setPaginator($paginator);

        $result = Result::create($request, $fractal->createData($collection)->toArray());

        return $result->createResponse();
    }

    /**
     * @param array $records
     * @return array
     */
    private function toRequestedRecordsArray(array $records)
    {
        $requestedRecords = [];

        foreach ($records as $item) {
            $requestedRecords[] = [
                'databox_id' => $item->databox_id,
                'record_id'  => $item->record_id,
            ];
        }

        return $requestedRecords;
    }

    /**
     * @param array $recordIds
     * @return \record_adapter[]
     */
    private function fetchRecords(array $recordIds)
    {
        $perDataboxRecords = [];

        foreach ($recordIds as $index => $record) {
            if (!isset($perDataboxRecords[$record['databox_id']])) {
                $perDataboxRecords[$record['databox_id']] = [];
            }

            $perDataboxRecords[$record['databox_id']][$record['record_id']] = $index;
        }

        $records = [];

        foreach ($perDataboxRecords as $databoxId => $recordIndexes) {
            $repository = $this->findDataboxById($databoxId)->getRecordRepository();

            foreach ($repository->findByRecordIds(array_keys($perDataboxRecords[$databoxId])) as $record) {
                $records[$recordIndexes[$record->getRecordId()]] = $record;
            }
        }

        ksort($records);

        return $records;
    }

    /**
     * @param \record_adapter[] $records
     * @return \record_adapter[]
     */
    private function filterOrderableRecords(array $records)
    {
        $acl = $this->getAclForUser();

        return array_filter($records, function (\record_adapter $record) use ($acl) {
            return $acl->has_right_on_base($record->getBaseId(), 'cancmd');
        });
    }
}
