<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\SearchEngineAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Repositories\StoryWZRepository;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\Twig\Fit;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordController extends Controller
{
    use EntityManagerAware;
    use SearchEngineAware;
    /**
     * Get record detailed view
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getRecord(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $searchEngine = $options = null;
        $train = '';

        if ('' === $env = strtoupper($request->get('env', ''))) {
            $this->app->abort(400, '`env` parameter is missing');
        }

        // Use $request->get as HTTP method can be POST or GET
        if ('RESULT' == $env = strtoupper($request->get('env', ''))) {
            try {
                $options = SearchEngineOptions::hydrate($this->app, $request->get('options_serial'));
                $searchEngine = $this->getSearchEngine();
            } catch (\Exception $e) {
                $this->app->abort(400, 'Search-engine options are not valid or missing');
            }
        }

        $pos = (int) $request->get('pos', 0);
        $query = $request->get('query', '');
        $reloadTrain = !! $request->get('roll', false);

        $record = new \record_preview(
            $this->app,
            $env,
            $pos < 0 ? 0 : $pos,
            $request->get('cont', ''),
            $searchEngine,
            $query,
            $options
        );

        $currentRecord = $this->getContainerResult($record);

        if ($record->is_from_reg()) {
            $train = $this->render('prod/preview/reg_train.html.twig', ['record' => $record]);
        } else if ($record->is_from_basket() && $reloadTrain) {
            $train = $this->render('prod/preview/basket_train.html.twig', ['record' => $record]);
        } else if ($record->is_from_feed()) {
            $train = $this->render('prod/preview/feed_train.html.twig', ['record' => $record]);
        }

        $recordCaptions = [];
        foreach ($record->get_caption()->get_fields(null, true) as $field) {
            // get field's values
            $recordCaptions[$field->get_name()] = $field->get_serialized_values();
        }
        $recordCaptions["technicalInfo"] = $record->getPositionFromTechnicalInfos();

        //  escape record title before rendering
        $recordTitle = explode("</span>", $record->get_title());
        if (count($recordTitle) >1) {
            $recordTitle[1] = htmlspecialchars($recordTitle[1]);
            $recordTitle = implode("</span>", $recordTitle);
        } else {
            $recordTitle = htmlspecialchars($record->get_title());
        }

        return $this->app->json([
            "desc"            => $this->render('prod/preview/caption.html.twig', [
                'record'        => $record,
                'highlight'     => $query,
                'searchEngine'  => $searchEngine,
                'searchOptions' => $options,
            ]),
            "recordCaptions"  => $recordCaptions,
            "html_preview"    => $this->render('common/preview.html.twig', [
                'record'        => $record
            ]),
            "others"          => $this->render('prod/preview/appears_in.html.twig', [
                'parents'       => $record->get_grouping_parents(),
                'baskets'       => $record->get_container_baskets($this->getEntityManager(), $this->getAuthenticatedUser()),
            ]),
            "current"         => $train,
            "record"          => $currentRecord,
            "history"         => $this->render('prod/preview/short_history.html.twig', [
                'record'        => $record,
            ]),
            "popularity"      => $this->render('prod/preview/popularity.html.twig', [
                'record'        => $record,
            ]),
            "tools"           => $this->render('prod/preview/tools.html.twig', [
                'record'        => $record,
            ]),
            "pos"             => $record->getNumber(),
            "title"           => $recordTitle,
            "databox_name"    => $record->getDatabox()->get_dbname(),
            "collection_name" => $record->getCollection()->get_name(),
            "collection_logo" => $record->getCollection()->getLogo($record->getBaseId(), $this->app),
        ]);
    }

    /**
     * @param \record_preview $recordContainer
     * @return array
     */
    private function getContainerResult(\record_preview $recordContainer)
    {
        /* @var $recordPreview \media_subdef */
        $helpers = new PhraseanetExtension($this->app);

        $recordData = [
          'databoxId' => $recordContainer->getBaseId(),
          'id' => $recordContainer->getId(),
          'isGroup' => $recordContainer->isStory(),
          'url' => (string)$helpers->getThumbnailUrl($recordContainer),
        ];
        $userHaveAccess = $this->app->getAclForUser($this->getAuthenticatedUser())->has_access_to_subdef($recordContainer, 'preview');
        if ($userHaveAccess) {
            $recordPreview = $recordContainer->get_preview();
        } else {
            $recordPreview = $recordContainer->get_thumbnail();
        }

        $recordData['preview'] = [
          'width' => $recordPreview->get_width(),
          'height' => $recordPreview->get_height(),
          'url' => $this->app->url('alchemy_embed_view', [
            'url' => (string)($this->getAuthenticatedUser() ? $recordPreview->get_url() : $recordPreview->get_permalink()->get_url()),
            'autoplay' => false
          ])
        ];

        return $recordData;
    }

    public function getRecordById($sbasId, $recordId)
    {
        $record = new \record_adapter($this->app, $sbasId, $recordId);
        return $this->app->json([
            "html_preview"  => $this->render('common/preview.html.twig', [
                'record'        => $record
            ]),
            "desc"  => $this->render('common/caption.html.twig', [
                'record'        => $record,
                'view'          => 'preview'
            ])
        ]);
    }

    /**
     *  Delete a record or a list of records
     *
     * @param  Request $request
     * @return Response
     */
    public function doDeleteRecords(Request $request)
    {
        $flatten = (bool)($request->request->get('del_children')) ? RecordsRequest::FLATTEN_YES_PRESERVE_STORIES : RecordsRequest::FLATTEN_NO;
        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,$flatten,
            [\ACL::CANDELETERECORD]
        );

        $basketElementsRepository = $this->getBasketElementRepository();
        $StoryWZRepository = $this->getStoryWorkZoneRepository();

        $deleted = [];

        /** @var \collection[] $trashCollectionsBySbasId */
        $trashCollectionsBySbasId = [];

        $manager = $this->getEntityManager();

        /** @var \record_adapter $record */
        foreach ($records as $record) {
            try {
                $basketElements = $basketElementsRepository->findElementsByRecord($record);

                foreach ($basketElements as $element) {
                    $manager->remove($element);
                    $deleted[] = $element->getRecord($this->app)->getId();
                }

                $attachedStories = $StoryWZRepository->findByRecord($this->app, $record);

                foreach ($attachedStories as $attachedStory) {
                    $manager->remove($attachedStory);
                }

                foreach($record->get_grouping_parents() as $story) {
                    $this->getEventDispatcher()->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($story));
                }

                $sbasId = $record->getDatabox()->get_sbas_id();
                if(!array_key_exists($sbasId, $trashCollectionsBySbasId)) {
                    $trashCollectionsBySbasId[$sbasId] = $record->getDatabox()->getTrashCollection();
                }
                $deleted[] = $record->getId();
                if($trashCollectionsBySbasId[$sbasId] !== null) {
                    if($record->getCollection()->get_coll_id() == $trashCollectionsBySbasId[$sbasId]->get_coll_id()) {
                        // record is already in trash so delete it
                        $record->delete();
                    } else {
                        // move to trash collection
                        $record->move_to_collection($trashCollectionsBySbasId[$sbasId], $this->getApplicationBox());
                        // disable permalinks
                        foreach($record->get_subdefs() as $subdef) {
                            if( ($pl = $subdef->get_permalink()) ) {
                                    $pl->set_is_activated(false);
                            }
                        }
                    }
                } else {
                    // no trash collection, delete
                    $record->delete();
                }
            } catch (\Exception $e) {
            }
        }

        $manager->flush();

        return $this->app->json($deleted);
    }

    /**
     * @return BasketElementRepository
     */
    private function getBasketElementRepository()
    {
        return $this->app['repo.basket-elements'];
    }

    /**
     * @return StoryWZRepository
     */
    private function getStoryWorkZoneRepository()
    {
        return $this->app['repo.story-wz'];
    }

    /**
     *  Delete a record or a list of records
     *
     * @param  Request $request
     * @return Response
     */
    public function whatCanIDelete(Request $request)
    {
        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,
            !!$request->request->get('del_children'),
            [\ACL::CANDELETERECORD]
        );

        $filteredRecord = $this->filterRecordToDelete($records);

        return $this->app->json([
            'renderView'     => $this->render('prod/actions/delete_records_confirm.html.twig', [
                'records'        => $records,
                'filteredRecord' => $filteredRecord
            ]),
            'filteredRecord' => $filteredRecord
        ]);

    }

    private function filterRecordToDelete(RecordsRequest $records)
    {
        $trashCollectionsBySbasId = [];
        $goingToTrash = [];
        $delete = [];
        foreach ($records as $record) {
            $sbasId = $record->getDatabox()->get_sbas_id();
            if (!array_key_exists($sbasId, $trashCollectionsBySbasId)) {
                $trashCollectionsBySbasId[$sbasId] = $record->getDatabox()->getTrashCollection();
            }
            if ($trashCollectionsBySbasId[$sbasId] !== null) {
                if ($record->getCollection()->get_coll_id() == $trashCollectionsBySbasId[$sbasId]->get_coll_id()) {
                    // record is already in trash
                    $delete[] = $record;
                }
                else {
                    // will be moved to trash
                    $goingToTrash[] = $record;
                }
            }
            else {
                // trash does not exist
                $delete[] = $record;
            }
        }
        //check if all values in array are true
        //return (!in_array(false, $goingToTrash, true));
        return ['trash' => $goingToTrash, 'delete' => $delete];
    }

    /**
     *  Renew url list of records
     *
     * @param Request $request
     *
     * @return Response
     */
    public function renewUrl(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, !!$request->request->get('renew_children_url'));

        $renewed = [];
        foreach ($records as $record) {
            $renewed[$record->getId()] = (string) $record->get_preview()->renew_url();
        };

        return $this->app->json($renewed);
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->app['dispatcher'];
    }
}
