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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Assert\Assertion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    /**
     * Search Records
     * @param Request $request
     * @return Response
     */
    public function searchAction(Request $request)
    {
        list($ret, $search_result) = $this->searchAndFormatEngineResult($request);

        /** @var SearchEngineResult $search_result */
        $ret['search_type'] = $search_result->getOptions()->getSearchType();
        $ret['results'] = [];

        foreach ($this->convertSearchResultToRecords($search_result->getResults()) as $record) {
            $ret['results'][] = [
                'databox_id' => $record->getDataboxId(),
                'record_id' => $record->getRecordId(),
                'collection_id' => $record->getCollectionId(),
                'updated_at' => $record->getUpdated(),
            ];
        }

        return Result::create($request, $ret)->createResponse();
    }

    private function searchAndFormatEngineResult(Request $request)
    {
        $options = SearchEngineOptions::fromRequest($this->app, $request);

        $offsetStart = (int) ($request->get('offset_start') ?: 0);
        $perPage = (int) $request->get('per_page') ?: 10;

        $query = (string) $request->get('query');
        $this->getSearchEngine()->resetCache();

        $search_result = $this->getSearchEngine()->query($query, $offsetStart, $perPage, $options);

        $this->getUserManipulator()->logQuery($this->getAuthenticatedUser(), $search_result->getQuery());

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function (\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $this->getSearchEngineLogger()
                ->log($databox, $search_result->getQuery(), $search_result->getTotal(), $colls);
        }

        $this->getSearchEngine()->clearCache();

        $ret = [
            'offset_start' => $offsetStart,
            'per_page' => $perPage,
            'available_results' => $search_result->getAvailable(),
            'total_results' => $search_result->getTotal(),
            'error' => (string)$search_result->getError(),
            'warning' => (string)$search_result->getWarning(),
            'query_time' => $search_result->getDuration(),
            'search_indexes' => $search_result->getIndexes(),
            'suggestions' => array_map(
                function (SearchEngineSuggestion $suggestion) {
                    return $suggestion->toArray();
                },
                $search_result->getSuggestions()->toArray()
            ),
            'facets' => $search_result->getFacets(),
            'results' => [],
        ];

        return [$ret, $search_result];
    }

    /**
     * @return SearchEngineInterface
     */
    private function getSearchEngine()
    {
        return $this->app['phraseanet.SE'];
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }

    /**
     * @return SearchEngineLogger
     */
    private function getSearchEngineLogger()
    {
        return $this->app['phraseanet.SE.logger'];
    }

    /**
     * @param RecordInterface[] $records
     * @return array[]
     */
    private function groupRecordIdsPerDataboxId($records)
    {
        $number = 0;
        $perDataboxRecordIds = [];

        foreach ($records as $record) {
            $databoxId = $record->getDataboxId();

            if (!isset($perDataboxRecordIds[$databoxId])) {
                $perDataboxRecordIds[$databoxId] = [];
            }

            $perDataboxRecordIds[$databoxId][$record->getRecordId()] = $number++;
        }

        return $perDataboxRecordIds;
    }

    /**
     * @param RecordInterface[] $records
     * @return \record_adapter[]
     */
    private function convertSearchResultToRecords($records)
    {
        Assertion::allIsInstanceOf($records, RecordInterface::class);

        $perDataboxRecordIds = $this->groupRecordIdsPerDataboxId($records);

        $records = [];

        foreach ($perDataboxRecordIds as $databoxId => $recordIds) {
            $databox = $this->findDataboxById($databoxId);

            foreach ($databox->getRecordRepository()->findByRecordIds(array_keys($recordIds)) as $record) {
                $records[$recordIds[$record->getRecordId()]] = $record;
            }
        }

        ksort($records);

        return $records;
    }
}
