<?php

namespace Alchemy\Phrasea\Controller\Api\V3;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Databox\DataboxGroupable;
use Alchemy\Phrasea\Databox\Record\LegacyRecordRepository;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefService;
use Alchemy\Phrasea\Fractal\CallbackTransformer;
use Alchemy\Phrasea\Fractal\IncludeResolver;
use Alchemy\Phrasea\Fractal\SearchResultTransformerResolver;
use Alchemy\Phrasea\Fractal\TraceableArraySerializer;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Record\RecordCollection;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Alchemy\Phrasea\Search\CaptionView;
use Alchemy\Phrasea\Search\PermalinkTransformer;
use Alchemy\Phrasea\Search\PermalinkView;
use Alchemy\Phrasea\Search\RecordTransformer;
use Alchemy\Phrasea\Search\RecordView;
use Alchemy\Phrasea\Search\SearchResultView;
use Alchemy\Phrasea\Search\StoryView;
use Alchemy\Phrasea\Search\SubdefTransformer;
use Alchemy\Phrasea\Search\SubdefView;
use Alchemy\Phrasea\Search\TechnicalDataTransformer;
use Alchemy\Phrasea\Search\TechnicalDataView;
use Alchemy\Phrasea\Search\V3SearchCompositeResultTransformer;
use Alchemy\Phrasea\Search\V3SearchResultTransformer;
use Alchemy\Phrasea\Search\V3StoryTransformer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Utilities\Stopwatch;
use caption_record;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Item;
use media_Permalink_Adapter;
use media_subdef;
use record_adapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class V3SearchController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;

    /**
     * Search for results
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $stopwatch = new Stopwatch("controller");

        $subdefTransformer = new SubdefTransformer($this->app['acl'], $this->getAuthenticatedUser(), new PermalinkTransformer());
        $technicalDataTransformer = new TechnicalDataTransformer();
        $recordTransformer = new RecordTransformer($subdefTransformer, $technicalDataTransformer);
        $storyTransformer = new V3StoryTransformer($recordTransformer);
        $compositeTransformer = new V3SearchCompositeResultTransformer($recordTransformer, $storyTransformer);
        $searchTransformer = new V3SearchResultTransformer($compositeTransformer);

        $transformerResolver = new SearchResultTransformerResolver([
            '' => $searchTransformer,
            'results' => $compositeTransformer,
            'facets' => new CallbackTransformer(),
            'suggestions' => new CallbackTransformer(),
            'results.stories' => $storyTransformer,
            'results.stories.children' => $recordTransformer,
            'results.stories.children.thumbnail' => $subdefTransformer,
            'results.stories.children.technical_informations' => $technicalDataTransformer,
            'results.stories.children.subdefs' => $subdefTransformer,
            'results.stories.children.metadata' => new CallbackTransformer(),
            'results.stories.children.status' => new CallbackTransformer(),
            'results.stories.children.caption' => new CallbackTransformer(),
            'results.records' => $recordTransformer,
            'results.records.thumbnail' => $subdefTransformer,
            'results.stories.thumbnail' => $subdefTransformer,
            'results.records.technical_informations' => $technicalDataTransformer,
            // 'results.stories.technical_informations' => $technicalDataTransformer,   // no ti on story
            'results.records.subdefs' => $subdefTransformer,
            'results.stories.subdefs' => $subdefTransformer,
            'results.records.metadata' => new CallbackTransformer(),
            'results.stories.metadata' => new CallbackTransformer(),
            'results.records.metadatas' => new CallbackTransformer(),
            'results.stories.metadatas' => new CallbackTransformer(),
            'results.records.caption' => new CallbackTransformer(),
            'results.stories.caption' => new CallbackTransformer(),
            'results.records.status' => new CallbackTransformer(),
            'results.stories.status' => new CallbackTransformer(),
        ]);

        $includeResolver = new IncludeResolver($transformerResolver);

        $fractal = new FractalManager();
        $fractal->setSerializer(new TraceableArraySerializer($this->app['dispatcher']));

        // and push everything back to fractal
        $fractal->parseIncludes($this->getIncludes($request));

        $stopwatch->lap("boot");

        $result = $this->doSearch($request);

        $stopwatch->lap("doSearch");

        $story_children_limit = null;
        // if searching stories
        if ($request->get('search_type') == 1) {
            $story_children_limit = (int)$request->get('story_children_limit') ?: 10;
        }

        $searchView = $this->buildSearchView(
            $result,
            $includeResolver->resolve($fractal),
            $this->resolveSubdefUrlTTL($request),
            $story_children_limit
        );

        $stopwatch->lap("buildSearchView");

        $ret = $fractal->createData(new Item($searchView, $searchTransformer))->toArray();

        $stopwatch->lap("fractal");

        return Result::create($request, $ret)->createResponse([$stopwatch]);
    }

    /**
     * Returns requested includes
     *
     * @param Request $request
     * @return string[]
     */
    private function getIncludes(Request $request)
    {
        // a local fractal manager will help to smartly parse the request parameters.
        $fractal = new FractalManager();

        //  first, get includes from request
        //
        $fractal->parseIncludes($request->get('include', []));
        $includes = $fractal->getRequestedIncludes();

        if ($request->attributes->get('_extended', false)) {
            // if ($request->get('search_type') != SearchEngineOptions::RECORD_STORY) {
            if(in_array('results.stories.children', $includes)) {
                $includes = array_merge($includes, [
                    'results.stories.caption',
                    'results.stories.metadata',
                    'results.stories.children.subdefs',
                    'results.stories.children.metadata',
                    'results.stories.children.caption',
                    'results.stories.children.status'
                ]);
            }
            else {
                $includes = array_merge($includes, [
                    'results.stories.caption'
                ]);
            }

            $includes = array_merge($includes, [
                'results.records.subdefs',
                'results.records.metadata',
                'results.records.caption',
                'results.records.status'
            ]);
        }

        // push back to fractal (it will keep values uniques)
        //
        $fractal->parseIncludes($includes);

        // finally get the result
        //
        return $fractal->getRequestedIncludes();
    }

    /**
     * @param SearchEngineResult $result
     * @param string[] $includes
     * @param int $urlTTL
     * @param int|null $story_max_records
     * @return SearchResultView
     */
    private function buildSearchView(SearchEngineResult $result, array $includes, $urlTTL, $story_max_records = null)
    {
        $references = new RecordReferenceCollection($result->getResults());

        $records = new RecordCollection();
        $stories = new RecordCollection();

        foreach ($references->toRecords($this->getApplicationBox()) as $record) {
            if ($record->isStory()) {
                $stories[$record->getId()] = $record;
            } else {
                $records[$record->getId()] = $record;
            }
        }

        $resultView = new SearchResultView($result);

        if ($stories->count() > 0) {
            $user = $this->getAuthenticatedUser();
            $children = [];
            $childrenCounts = [];

            // todo : refacto to remove over-usage of array_map, array_combine, array_flip etc.
            //
            foreach ($stories->getDataboxIds() as $databoxId) {
                $storyIds = $stories->getDataboxRecordIds($databoxId);

                /** @var LegacyRecordRepository $repo */
                $repo = $this->findDataboxById($databoxId)->getRecordRepository();

                // nb : findChildren() and getChildrenCounts() acts on MULTIPLE story-ids in single sql
                //
                $childrenCounts[$databoxId] = $repo->getChildrenCounts($storyIds, $user);

                // search children only if needed
                //
                if(in_array('results.stories.children', $includes, true)) {
                    $selections = $repo->findChildren($storyIds, $user, 0, $story_max_records);
                    $children[$databoxId] = array_combine($storyIds, $selections);
                }
            }

            /** @var StoryView[] $storyViews */
            $storyViews = [];
            /** @var RecordView[] $childrenViews */
            $childrenViews = [];

            foreach ($stories as $index => $story) {
                $storyView = new StoryView($story);

                // populate children only if needed
                //
                if(in_array('results.stories.children', $includes, true)) {
                    $selection = $children[$story->getDataboxId()][$story->getRecordId()];

                    $childrenView = $this->buildRecordViews($selection);

                    foreach ($childrenView as $view) {
                        $childrenViews[spl_object_hash($view)] = $view;
                    }

                    $storyView->setChildren($childrenView);
                }

                $storyView->setData('childrenOffset', 0);
                $storyView->setData('childrenLimit', $story_max_records);
                $storyView->setData('childrenCount', $childrenCounts[$story->getDataboxId()][$story->getRecordId()]);

                $storyViews[$index] = $storyView;
            }

            if (in_array('results.stories.thumbnail', $includes, true)) {
                $subdefViews = $this->buildSubdefsViews($stories, ['thumbnail'], $urlTTL);

                foreach ($storyViews as $index => $storyView) {
                    $storyView->setSubdefs($subdefViews[$index]);
                }
            }

            if (in_array('results.stories.metadata', $includes, true) ||
                in_array('results.stories.caption', $includes, true)) {
                $captions = $this->app['service.caption']->findByReferenceCollection($stories);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($stories);

                $this->buildCaptionViews($storyViews, $captions, $canSeeBusiness);
            }

            $allChildren = new RecordCollection();
            foreach ($childrenViews as $index => $childrenView) {
                $allChildren[$index] = $childrenView->getRecord();
            }

            $names = in_array('results.stories.children.subdefs', $includes, true) ? null : ['thumbnail'];
            $subdefViews = $this->buildSubdefsViews($allChildren, $names, $urlTTL);
            $technicalDatasets = $this->app['service.technical_data']->fetchRecordsTechnicalData($allChildren);

            foreach ($childrenViews as $index => $recordView) {
                $recordView->setSubdefs($subdefViews[$index]);
                $recordView->setTechnicalDataView(new TechnicalDataView($technicalDatasets[$index]));
            }

            if (array_intersect($includes, ['results.stories.children.metadata', 'results.stories.children.caption'])) {
                $captions = $this->app['service.caption']->findByReferenceCollection($allChildren);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($allChildren);

                $this->buildCaptionViews($childrenViews, $captions, $canSeeBusiness);
            }

            $resultView->setStories($storyViews);
        }

        if ($records->count() > 0) {
            $names = in_array('results.records.subdefs', $includes, true) ? null : ['thumbnail'];
            $recordViews = $this->buildRecordViews($records);
            $subdefViews = $this->buildSubdefsViews($records, $names, $urlTTL);

            $technicalDatasets = $this->app['service.technical_data']->fetchRecordsTechnicalData($records);

            foreach ($recordViews as $index => $recordView) {
                $recordView->setSubdefs($subdefViews[$index]);
                $recordView->setTechnicalDataView(new TechnicalDataView($technicalDatasets[$index]));
            }

            if (array_intersect($includes, ['results.records.metadata', 'results.records.caption'])) {
                $captions = $this->app['service.caption']->findByReferenceCollection($records);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($records);

                $this->buildCaptionViews($recordViews, $captions, $canSeeBusiness);
            }

            $resultView->setRecords($recordViews);
        }

        return $resultView;
    }

    /**
     * @param Request $request
     * @return SearchEngineResult
     */
    private function doSearch(Request $request)
    {
        list($offset, $limit) = V3ResultHelpers::paginationFromRequest($request);

        $options = SearchEngineOptions::fromRequest($this->app, $request);

        $options->setFirstResult($offset);
        $options->setMaxResults($limit);

        $this->getSearchEngine()->resetCache();

        $search_result = $this->getSearchEngine()->query((string)$request->get('query'), $options);

        $this->getUserManipulator()->logQuery($this->getAuthenticatedUser(), $search_result->getQueryText());

        // log array of collectionIds (from $options) for each databox
        $collectionsReferencesByDatabox = $options->getCollectionsReferencesByDatabox();
        foreach ($collectionsReferencesByDatabox as $sbid => $references) {
            $databox = $this->findDataboxById($sbid);
            $collectionsIds = array_map(function(CollectionReference $ref){return $ref->getCollectionId();}, $references);
            $this->getSearchEngineLogger()->log($databox, $search_result->getQueryText(), $search_result->getTotal(), $collectionsIds);
        }

        $this->getSearchEngine()->clearCache();

        return $search_result;
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
     * @param Request $request
     * @return int
     */
    private function resolveSubdefUrlTTL(Request $request)
    {
        $urlTTL = $request->query->get('subdef_url_ttl');

        if (null !== $urlTTL) {
            return (int)$urlTTL;
        }

        return $this->getConf()->get(['registry', 'general', 'default-subdef-url-ttl']);
    }

    /**
     * @param RecordCollection|record_adapter[] $references
     * @return RecordView[]
     */
    private function buildRecordViews($references)
    {
        if (!$references instanceof RecordCollection) {
            $references = new RecordCollection($references);
        }

        $recordViews = [];

        foreach ($references as $index => $record) {
            $recordViews[$index] = new RecordView($record);
        }

        return $recordViews;
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection|DataboxGroupable $references
     * @param array|null $names
     * @param int $urlTTL
     * @return SubdefView[][]
     */
    private function buildSubdefsViews($references, array $names = null, $urlTTL)
    {
        /** @var MediaSubdefService $MediaSubdefService */
        $MediaSubdefService = $this->app['service.media_subdef'];
        $subdefGroups = $MediaSubdefService
            ->findSubdefsByRecordReferenceFromCollection($references, $names);

        $fakeSubdefs = [];

        foreach ($subdefGroups as $index => $subdefGroup) {
            if (!isset($subdefGroup['thumbnail'])) {
                $fakeSubdef = new media_subdef($this->app, $references[$index], 'thumbnail', true, []);
                $fakeSubdefs[spl_object_hash($fakeSubdef)] = $fakeSubdef;

                $subdefGroups[$index]['thumbnail'] = $fakeSubdef;
            }
        }

        $allSubdefs = $this->mergeGroupsIntoOneList($subdefGroups);
        $allPermalinks = media_Permalink_Adapter::getMany(
            $this->app,
            array_filter($allSubdefs, function (media_subdef $subdef) use ($fakeSubdefs) {
                return !isset($fakeSubdefs[spl_object_hash($subdef)]);
            })
        );

        /** @var MediaSubDefinitionUrlGenerator $urlGenerator */
        $urlGenerator = $this->app['media_accessor.subdef_url_generator'];
        $urls = $urlGenerator->generateMany($this->getAuthenticatedUser(), $allSubdefs, $urlTTL);

        $subdefViews = [];

        /** @var media_subdef $subdef */
        foreach ($allSubdefs as $index => $subdef) {
            $subdefView = new SubdefView($subdef);

            if (isset($allPermalinks[$index])) {
                $subdefView->setPermalinkView(new PermalinkView($allPermalinks[$index]));
            }

            $subdefView->setUrl($urls[$index]);
            $subdefView->setUrlTTL($urlTTL);

            $subdefViews[spl_object_hash($subdef)] = $subdefView;
        }

        $reorderedGroups = [];

        /** @var media_subdef[] $subdefGroup */
        foreach ($subdefGroups as $index => $subdefGroup) {
            $reordered = [];

            foreach ($subdefGroup as $subdef) {
                $reordered[] = $subdefViews[spl_object_hash($subdef)];
            }

            $reorderedGroups[$index] = $reordered;
        }

        return $reorderedGroups;
    }

    /**
     * @param array $groups
     * @return array|mixed
     */
    private function mergeGroupsIntoOneList(array $groups)
    {
        // Strips keys from the internal array
        array_walk($groups, function (array &$group) {
            $group = array_values($group);
        });

        if ($groups) {
            return call_user_func_array('array_merge', $groups);
        }

        return [];
    }

    /**
     * @param RecordReferenceInterface[]|DataboxGroupable $references
     * @return array<int, bool>
     */
    private function retrieveSeeBusinessPerDatabox($references)
    {
        if (!$references instanceof DataboxGroupable) {
            $references = new RecordReferenceCollection($references);
        }

        $acl = $this->getAclForUser();

        $canSeeBusiness = [];

        foreach ($references->getDataboxIds() as $databoxId) {
            $canSeeBusiness[$databoxId] = $acl->can_see_business_fields($this->findDataboxById($databoxId));
        }

        $rights = [];

        foreach ($references as $index => $reference) {
            $rights[$index] = $canSeeBusiness[$reference->getDataboxId()];
        }

        return $rights;
    }

    /**
     * @param RecordView[] $recordViews
     * @param caption_record[] $captions
     * @param bool[] $canSeeBusiness
     */
    private function buildCaptionViews($recordViews, $captions, $canSeeBusiness)
    {
        foreach ($recordViews as $index => $recordView) {
            $caption = $captions[$index];

            $captionView = new CaptionView($caption);

            $captionView->setFields($caption->get_fields(null, isset($canSeeBusiness[$index]) && (bool)$canSeeBusiness[$index]));

            $recordView->setCaption($captionView);
        }
    }
}
