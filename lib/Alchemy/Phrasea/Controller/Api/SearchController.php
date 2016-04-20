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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
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

        foreach ($search_result->getResults() as $record) {
            $ret['results'][] = [
                'databox_id' => $record->getDataboxId(),
                'record_id' => $record->getRecordId(),
                'collection_id' => $record->getCollectionId(),
                'version' => $record->getUpdated()->getTimestamp(),
            ];
        }

        return Result::create($request, $ret)->createResponse();
    }

    private function searchAndFormatEngineResult(Request $request)
    {
        $options = SearchEngineOptions::fromRequest($this->app, $request);
        $options->setFirstResult($request->get('offset_start') ?: 0);
        $options->setMaxResults($request->get('per_page') ?: 10);

        $query = (string) $request->get('query');
        $this->getSearchEngine()->resetCache();

        $search_result = $this->getSearchEngine()->query($query, $options);

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
            'offset_start' => $options->getFirstResult(),
            'per_page' => $options->getMaxResults(),
            'available_results' => $search_result->getAvailable(),
            'total_results' => $search_result->getTotal(),
            'error' => (string)$search_result->getError(),
            'warning' => (string)$search_result->getWarning(),
            'query_time' => $search_result->getDuration(),
            'search_indexes' => $search_result->getIndexes(),
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
}
