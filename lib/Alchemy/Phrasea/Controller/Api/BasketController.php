<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\Basket\ElementsAdded;
use Alchemy\Phrasea\Core\Event\Basket\ElementsRemoved;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Basket;
use Symfony\Component\HttpFoundation\Request;

class BasketController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use JsonBodyAware;

    public function addRecordsAction(Request $request, Basket $basket)
    {
        $data = $this->decodeJsonBody($request, 'records.json');

        $requestedRecords = $this->toArray($data);
        $records = $this->fetchRecords($requestedRecords);
        $errors = $records['errors'];

        $accessibleRecords = $this->filterAccessibleRecords($records['records']);
        $toAdd = $this->filterAlreadyInBasket($basket, $accessibleRecords);

        $records = $this->reorderRecords($requestedRecords, $toAdd);

        $elements = $this->app['manipulator.basket']->addRecords($basket, $records);

        $result = Result::create($request, ['elements' => $elements]);

        $this->dispatch(PhraseaEvents::BASKET_ELEMENTS_ADDED, new ElementsAdded(
            $basket,
            $requestedRecords,
            $elements,
            $errors
        ));

        return $result->createResponse();
    }

    public function removeRecordsAction(Request $request, Basket $basket)
    {
        $data = $this->decodeJsonBody($request, 'records.json');

        $requestedRecords = $this->toArray($data);

        $elements = $this->app['repo.basket-elements']->findByRecords($requestedRecords, $basket->getId());

        $this->app['manipulator.basket']->removeElements($basket, $elements);

        $result = Result::create($request, ['elements' => $elements]);

        $this->dispatch(PhraseaEvents::BASKET_ELEMENTS_REMOVED, new ElementsRemoved($basket, $requestedRecords, $elements));

        return $result->createResponse();
    }

    public function reorderRecordsAction(Request $request, Basket $basket)
    {
    }

    /**
     * @param object $object
     * @return array
     */
    private function toArray($object)
    {
        $requestedRecords = [];

        foreach ($object->data as $item) {
            $requestedRecords[] = [
                'databox_id' => $item->databox_id,
                'record_id'  => $item->record_id,
            ];
        }

        return $requestedRecords;
    }

    /**
     * @param array $records
     * @return array
     */
    private function groupByDatabox(array $records)
    {
        $perDataboxRecords = [];

        foreach ($records as $record) {
            $databoxId = $record['databox_id'];
            if (!isset($perDataboxRecords[$databoxId])) {
                $perDataboxRecords[$databoxId] = [];
            }
            $perDataboxRecords[$databoxId][] = $record['record_id'];
        }

        return $perDataboxRecords;
    }

    /**
     * @param array $requestedRecords
     * @return array
     */
    private function fetchRecords(array $requestedRecords)
    {
        $perDataboxRecords = $this->groupByDatabox($requestedRecords);

        $errors = [];
        $records = [];
        foreach ($perDataboxRecords as $databoxId => $recordIds) {
            try {
                $databox = $this->findDataboxById($databoxId);
            } catch (\Exception $exception) {
                $errors[] = $exception;
                continue;
            }

            $records = array_merge($records, $databox->getRecordRepository()->findByRecordIds($recordIds));
        }

        return [
            'records' => $records,
            'errors' => $errors,
        ];
    }

    /**
     * @param \record_adapter[] $records
     * @return array
     * @throws \Alchemy\Phrasea\Cache\Exception
     */
    private function filterAccessibleRecords(array $records)
    {
        $acl = $this->getAclForUser();

        // Check rights on retrieved records
        $accessibleRecords = [];

        foreach ($records as $record) {
            if ($acl->has_access_to_record($record)) {
                if (!$record->isStory()) {
                    $accessibleRecords[] = $record;
                    continue;
                }

                $children = $record->getChildren();

                foreach ($children as $child) {
                    if ($acl->has_access_to_record($child)) {
                        $accessibleRecords[] = $child;
                    }
                }
            }
        }

        return $accessibleRecords;
    }

    /**
     * @param Basket            $basket
     * @param \record_adapter[] $records
     * @return \record_adapter[]
     */
    private function filterAlreadyInBasket(Basket $basket, array $records)
    {
        $toAdd = [];

        foreach ($records as $record) {
            if ($basket->hasRecord($this->app, $record)) {
                continue;
            }

            $toAdd[] = $record;
        }

        return $toAdd;
    }

    /**
     * @param array             $requestedRecords
     * @param \record_adapter[] $toAdd
     * @return \record_adapter[]
     */
    private function reorderRecords(array $requestedRecords, array $toAdd)
    {
        $records = [];

        foreach ($requestedRecords as $requestedRecord) {
            foreach ($toAdd as $index => $record) {
                if ($record->getDataboxId() == $requestedRecord['databox_id']
                    && $record->getRecordId() == $requestedRecord['record_id']
                ) {
                    $records[] = $record;
                    unset($toAdd[$index]);
                    continue 2;
                }
            }
        }

        return $records;
    }
}
