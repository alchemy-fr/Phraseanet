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

use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Fractal\ArraySerializer;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Search\SearchResultView;
use Alchemy\Phrasea\Search\V2SearchTransformer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
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
        $fractal = new Manager();
        $fractal->setSerializer(new ArraySerializer());
        $fractal->parseIncludes([]);

        $searchView = new SearchResultView($this->doSearch($request));
        $ret = $fractal->createData(new Item($searchView, new V2SearchTransformer()))->toArray();

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @param Request $request
     * @return SearchEngineResult
     */
    private function doSearch(Request $request)
    {
        $options = SearchEngineOptions::fromRequest($this->app, $request);
        $options->setFirstResult($request->get('offset_start') ?: 0);
        $options->setMaxResults($request->get('per_page') ?: 10);

        $query = (string) $request->get('query');
        $this->getSearchEngine()->resetCache();

        $result = $this->getSearchEngine()->query($query, $options);

        $this->getUserManipulator()->logQuery($this->getAuthenticatedUser(), $result->getQueryText());

        // log array of collectionIds (from $options) for each databox
        $collectionsReferencesByDatabox = $options->getCollectionsReferencesByDatabox();
        foreach ($collectionsReferencesByDatabox as $sbid => $references) {
            $databox = $this->findDataboxById($sbid);
            $collectionsIds = array_map(function(CollectionReference $ref){return $ref->getCollectionId();}, $references);
            $this->getSearchEngineLogger()->log($databox, $result->getQueryText(), $result->getTotal(), $collectionsIds);
        }

        $this->getSearchEngine()->clearCache();

        return $result;
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
