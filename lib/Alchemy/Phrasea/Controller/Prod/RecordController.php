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

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\SearchEngineAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\Record\DeleteEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Repositories\StoryWZRepository;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use record_adapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @return JsonResponse
     * @throws \Exception
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

        $recordTitle = $this->render('prod/preview/title.html.twig', ['record' => $record]);

        $containerType = null;

        if ($env === 'BASK') {
            /** @var Basket $basket */
            $basket = $record->get_container();

            if ($basket->getPusher()) {
                $containerType = 'push_rec';
            }

            if ($this->getAuthenticatedUser()->getId() != $basket->getUser()->getId() && $basket->isParticipant($this->getAuthenticatedUser())) {
                if ($basket->isVoteBasket()) {
                    $containerType = 'feedback_rec';
                } else {
                    $containerType = 'share_rec';
                }
            } elseif ($this->getAuthenticatedUser()->getId() == $basket->getUser()->getId() && count($basket->getParticipants()) > 0 ) {
                if ($basket->isVoteBasket()) {
                    $containerType = empty($containerType) ? 'feedback_sent' : 'feedback_push';
                } else {
                    $containerType = empty($containerType) ? 'share_sent' : 'share_push';
                }
            } else {
                $containerType = 'basket';
            }
        } elseif ($env === 'REG') {
            $containerType = 'regroup';
        }

        $basketElementsRepository = $this->getBasketElementRepository();
        $feedbackElementDatas = $basketElementsRepository->findElementsDatasByRecord($record);

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
            "votingNotice"  => $this->render('prod/preview/voting_notice.html.twig', [
                'feedbackElementDatas' => $feedbackElementDatas
            ]),
            "pos"             => $record->getNumber(),
            "title"           => $recordTitle,
            "containerType"   => $containerType,
            "databox_name"    => $record->getDatabox()->get_label($this->app['locale']),
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
        $record = new record_adapter($this->app, $sbasId, $recordId);
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
        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,
            RecordsRequest::FLATTEN_NO,
            [\ACL::CANDELETERECORD]
        );

        $basketElementsRepository = $this->getBasketElementRepository();
        $StoryWZRepository = $this->getStoryWorkZoneRepository();

        $deleted = [];

        /** @var \collection[] $trashCollectionsBySbasId */
        $trashCollectionsBySbasId = [];

        $manager = $this->getEntityManager();

        /** @var record_adapter $record */
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

                foreach ($record->get_grouping_parents() as $story) {
                    $this->getEventDispatcher()->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($story));
                }

                $sbasId = $record->getDatabox()->get_sbas_id();
                if (!array_key_exists($sbasId, $trashCollectionsBySbasId)) {
                    $trashCollectionsBySbasId[$sbasId] = $record->getDatabox()->getTrashCollection();
                }
                $deleted[] = $record->getId();
                if ($trashCollectionsBySbasId[$sbasId] !== null) {
                    if($record->getCollection()->get_coll_id() == $trashCollectionsBySbasId[$sbasId]->get_coll_id()) {
                        // record is already in trash so delete it
                        $this->getEventDispatcher()->dispatch(RecordEvents::DELETE, new DeleteEvent($record));
                    } else {
                        // move to trash collection
                        $record->move_to_collection($trashCollectionsBySbasId[$sbasId]);
                        // disable permalinks
                        foreach($record->get_subdefs() as $subdef) {
                            if( ($pl = $subdef->get_permalink()) ) {
                                    $pl->set_is_activated(false);
                            }
                        }
                    }
                } else {
                    // no trash collection, delete
                    $this->getEventDispatcher()->dispatch(RecordEvents::DELETE, new DeleteEvent($record));
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
     * @return string   html
     */
    public function whatCanIDelete(Request $request)
    {
        $viewParms = [];

        // pre-count records that would be trashed/deleted when the "deleted children" will be un-checked

        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,
            RecordsRequest::FLATTEN_NO,
            [\ACL::CANDELETERECORD]
        );

        $filteredRecords = $this->filterRecordToDelete($records);

        $viewParms['parents_only'] = [
            'records'        => $records,
            'trashableCount' => count($filteredRecords['trash']),
            'deletableCount' => count($filteredRecords['delete'])
        ];

        // pre-count records that would be trashed/deleted when the "deleted children" will be checked
        //
        $records = RecordsRequest::fromRequest(
            $this->app,
            $request,
            RecordsRequest::FLATTEN_YES_PRESERVE_STORIES,
            [\ACL::CANDELETERECORD]
        );
        $filteredRecords = $this->filterRecordToDelete($records);
        $viewParms['with_children'] = [
            'records'        => $records,
            'trashableCount' => count($filteredRecords['trash']),
            'deletableCount' => count($filteredRecords['delete'])
        ];

        return $this->render(
            'prod/actions/delete_records_confirm.html.twig',
            $viewParms
        );

    }

    /**
     * classifies records in two groups (does NOT delete anything)
     * - 'trash'  : the record can go to trash because the db has a "_TRASH_" coll, and the record is not already into it
     * - 'delete' : the record would be deleted because the db has no trash, or the record is already trashed
     *
     * @param RecordsRequest $records
     * @return array
     */
    private function filterRecordToDelete(RecordsRequest $records)
    {
        $ret = [
            'trash'  => [],
            'delete' => []
        ];

        $trashCollectionsBySbasId = [];
        foreach ($records as $record) {
            /** @var record_adapter $record */
            $sbasId = $record->getDatabox()->get_sbas_id();
            if (!array_key_exists($sbasId, $trashCollectionsBySbasId)) {
                $trashCollectionsBySbasId[$sbasId] = $record->getDatabox()->getTrashCollection();
            }
            if ($trashCollectionsBySbasId[$sbasId] !== null) {
                if ($record->getCollection()->get_coll_id() == $trashCollectionsBySbasId[$sbasId]->get_coll_id()) {
                    // record is already in trash
                    $ret['delete'][] = $record;
                }
                else {
                    // will be moved to trash
                    $ret['trash'][] = $record;
                }
            }
            else {
                // trash does not exist
                $ret['delete'][] = $record;
            }
        }

        return $ret;
    }

    /**
     *  Renew url list of records
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Alchemy\Phrasea\Cache\Exception
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
