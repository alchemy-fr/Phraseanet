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
use Alchemy\Phrasea\Order\OrderFiller;
use Doctrine\Common\Collections\ArrayCollection;
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

        $result = Result::create($request, [
            'order' => $order,
        ]);

        $this->dispatch(PhraseaEvents::ORDER_CREATE, new OrderEvent($order));

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

            foreach ($repository->findByRecordIds($perDataboxRecords[$databoxId]) as $record) {
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
            return $acl->has_right_on_base($record->getBaseId(), 'can_cmd');
        });
    }
}
