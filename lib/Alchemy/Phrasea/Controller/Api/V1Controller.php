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

use Alchemy\Phrasea\Account\AccountException;
use Alchemy\Phrasea\Account\AccountService;
use Alchemy\Phrasea\Account\CollectionRequestMapper;
use Alchemy\Phrasea\Account\Command\UpdateAccountCommand;
use Alchemy\Phrasea\Account\Command\UpdatePasswordCommand;
use Alchemy\Phrasea\Account\RestrictedStatusExtractor;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Authentication\Exception\RegistrationException;
use Alchemy\Phrasea\Authentication\RegistrationService;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Checker\Response as CheckerResponse;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\Visa;
use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\Databox\DataboxGroupable;
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Form\Login\PhraseaRenewPasswordForm;
use Alchemy\Phrasea\Fractal\ArraySerializer;
use Alchemy\Phrasea\Fractal\CallbackTransformer;
use Alchemy\Phrasea\Fractal\IncludeResolver;
use Alchemy\Phrasea\Fractal\SearchResultTransformerResolver;
use Alchemy\Phrasea\Fractal\TraceableArraySerializer;
use Alchemy\Phrasea\Model\Entities\ApiOauthToken;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\LazaretCheck;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\RecordReferenceInterface;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\FeedEntryRepository;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Alchemy\Phrasea\Model\Repositories\LazaretFileRepository;
use Alchemy\Phrasea\Model\Repositories\TaskRepository;
use Alchemy\Phrasea\Record\RecordCollection;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Alchemy\Phrasea\Search\CaptionView;
use Alchemy\Phrasea\Search\PermalinkTransformer;
use Alchemy\Phrasea\Search\PermalinkView;
use Alchemy\Phrasea\Search\RecordTransformer;
use Alchemy\Phrasea\Search\RecordView;
use Alchemy\Phrasea\Search\SearchResultView;
use Alchemy\Phrasea\Search\StoryTransformer;
use Alchemy\Phrasea\Search\StoryView;
use Alchemy\Phrasea\Search\SubdefTransformer;
use Alchemy\Phrasea\Search\SubdefView;
use Alchemy\Phrasea\Search\TechnicalDataTransformer;
use Alchemy\Phrasea\Search\TechnicalDataView;
use Alchemy\Phrasea\Search\V1SearchCompositeResultTransformer;
use Alchemy\Phrasea\Search\V1SearchRecordsResultTransformer;
use Alchemy\Phrasea\Search\V1SearchResultTransformer;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Alchemy\Phrasea\Status\StatusStructure;
use Alchemy\Phrasea\TaskManager\LiveInformation;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Annotation\Ignore;
use League\Fractal\Resource\Item;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class V1Controller extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use JsonBodyAware;

    const OBJECT_TYPE_USER = 'http://api.phraseanet.com/api/objects/user';
    const OBJECT_TYPE_STORY = 'http://api.phraseanet.com/api/objects/story';
    const OBJECT_TYPE_STORY_METADATA_BAG = 'http://api.phraseanet.com/api/objects/story-metadata-bag';

    public function getBadRequestAction(Request $request, $message = '')
    {
        $response = Result::createError($request, 400, $message)->createResponse();
        $response->headers->set('X-Status-Code', $response->getStatusCode());

        return $response;
    }

    /**
     * Return an array of key-values information about scheduler
     *
     * @param Request $request
     * @return Response
     */
    public function getSchedulerAction(Request $request)
    {
        /** @var LiveInformation $information */
        $information = $this->app['task-manager.live-information'];
        $data = $information->getManager();

        return Result::create($request, [
            'scheduler' => [
                'configuration' => $data['configuration'],
                'state' => $data['actual'],
                'status' => $data['actual'],
                'pid' => $data['process-id'],
                'process-id' => $data['process-id'],
                'updated_on' => (new \DateTime())->format(DATE_ATOM),
            ],
        ])->createResponse();
    }

    public function indexTasksAction(Request $request)
    {
        $ret = array_map(function (Task $task) {
            return $this->showTask($task);
        }, $this->getTaskRepository()->findAll());

        return Result::create($request, ['tasks' => $ret])->createResponse();
    }

    private function showTask(Task $task)
    {
        /** @var LiveInformation $information */
        $information = $this->app['task-manager.live-information'];
        $data = $information->getTask($task);

        return [
            'id'             => $task->getId(),
            'title'          => $task->getName(),
            'name'           => $task->getName(),
            'state'          => $task->getStatus(),
            'status'         => $task->getStatus(),
            'actual-status'  => $data['actual'],
            'process-id'     => $data['process-id'],
            'pid'            => $data['process-id'],
            'jobId'          => $task->getJobId(),
            'period'         => $task->getPeriod(),
            'last_exec_time' => NullableDateTime::format($task->getLastExecution()),
            'last_execution' => NullableDateTime::format($task->getLastExecution()),
            'updated'        => NullableDateTime::format($task->getUpdated()),
            'created'        => NullableDateTime::format($task->getCreated()),
            'auto_start'     => $task->getStatus() === Task::STATUS_STARTED,
            'crashed'        => $task->getCrashed(),
        ];
    }

    public function showTaskAction(Request $request, Task $task)
    {
        return Result::create($request, ['task' => $this->showTask($task)])->createResponse();
    }

    public function startTaskAction(Request $request, Task $task)
    {
        $this->getTaskManipulator()->start($task);

        return $this->showTaskAction($request, $task);
    }

    public function stopTaskAction(Request $request, Task $task)
    {
        $this->getTaskManipulator()->stop($task);

        return $this->showTaskAction($request, $task);
    }

    public function setTaskPropertyAction(Request $request, Task $task)
    {
        $title = $request->get('title');
        $autostart = $request->get('autostart');

        if (null === $title && null === $autostart) {
            return $this->getBadRequestAction($request);
        }

        if ($title) {
            $task->setName($title);
        }
        if ($autostart) {
            $task->setStatus(Task::STATUS_STARTED);
        }

        return $this->showTaskAction($request, $task);
    }

    private function getCacheInformation()
    {
        $caches = [
            'main'               => $this->app['cache'],
            'op_code'            => $this->app['opcode-cache'],
            'doctrine_metadatas' => $this->app['orm.em']->getConfiguration()->getMetadataCacheImpl(),
            'doctrine_query'     => $this->app['orm.em']->getConfiguration()->getQueryCacheImpl(),
            'doctrine_result'    => $this->app['orm.em']->getConfiguration()->getResultCacheImpl(),
        ];

        $ret = [];

        foreach ($caches as $name => $service) {
            if ($service instanceof Cache) {
                $ret['cache'][$name] = [
                    'type'   => $service->getName(),
                    'online' => $service->isOnline(),
                    'stats'  => $service->getStats(),
                ];
            } else {
                $ret['cache'][$name] = null;
            }
        }

        return $ret;
    }

    private function getConfigInformation()
    {
        $ret = [];

        /** @var Version $version */
        $version = $this->app['phraseanet.version'];
        $ret['phraseanet']['version'] = [
            'name'   => $version->getName(),
            'number' => $version->getNumber(),
        ];

        $ret['phraseanet']['environment'] = $this->app->getEnvironment();
        $ret['phraseanet']['debug'] = $this->app['debug'];
        $conf = $this->getConf();
        $ret['phraseanet']['maintenance'] = $conf->get(['main', 'maintenance']);
        $ret['phraseanet']['errorsLog'] = $this->app['debug'];
        $ret['phraseanet']['serverName'] = $conf->get('servername');

        return $ret;
    }

    private function getGlobalValuesInformation()
    {
        /** @var SearchEngineInterface $searchEngine */
        $searchEngine = $this->app['phraseanet.SE'];
        try {
            $SEStatus = $searchEngine->getStatus();
        } catch (\RuntimeException $e) {
            $SEStatus = ['error' => $e->getMessage()];
        }

        $conf = $this->getConf();
        $binaries = $conf->get(['main', 'binaries']);

        return [
            'global_values' => [
                'serverName' => $conf->get('servername'),
                'title' => $conf->get(['registry', 'general', 'title']),
                'keywords' => $conf->get(['registry', 'general', 'keywords']),
                'description' => $conf->get(['registry', 'general', 'description']),
                'httpServer' => [
                    'phpTimezone' => ini_get('date.timezone'),
                    'siteId' => $conf->get(['main', 'key']),
                    'defaultLanguage' => $conf->get(['languages', 'default']),
                    'allowIndexing' => $conf->get(['registry', 'general', 'allow-indexation']),
                    'modes' => [
                        'XsendFile' => $conf->get(['xsendfile', 'enabled']),
                        'XsendFileMapping' => $conf->get(['xsendfile', 'mapping']),
                        'h264Streaming' => $conf->get(['registry', 'executables', 'h264-streaming-enabled']),
                        'authTokenDirectory' => $conf->get(['registry', 'executables', 'auth-token-directory']),
                        'authTokenDirectoryPath' => $conf->get(['registry', 'executables', 'auth-token-directory-path']),
                        'authTokenPassphrase' => $conf->get(['registry', 'executables', 'auth-token-passphrase']),
                    ],
                ],
                'maintenance'       => [
                    'alertMessage'   => $conf->get(['registry', 'maintenance', 'message']),
                    'displayMessage' => $conf->get(['registry', 'maintenance', 'enabled']),
                ],
                'webServices'       => [
                    'googleApi'         => $conf->get(['registry', 'webservices', 'google-charts-enabled']),
                    'googleAnalyticsId' => $conf->get(['registry', 'general', 'analytics']),
                    'i18nWebService'    => $conf->get(['registry', 'webservices', 'geonames-server']),
                    'recaptacha'        => [
                        'active'     => $conf->get(['registry', 'webservices', 'captcha-enabled']),
                        'publicKey'  => $conf->get(['registry', 'webservices', 'recaptcha-public-key']),
                        'privateKey' => $conf->get(['registry', 'webservices', 'recaptcha-private-key']),
                    ],
                    'youtube'           => [
                        'active'       => $conf->get(['main', 'bridge', 'youtube', 'enabled']),
                        'clientId'     => $conf->get(['main', 'bridge', 'youtube', 'client_id']),
                        'clientSecret' => $conf->get(['main', 'bridge', 'youtube', 'client_secret']),
                        'devKey'       => $conf->get(['main', 'bridge', 'youtube', 'developer_key']),
                    ],
                    'flickr'            => [
                        'active'       => $conf->get(['main', 'bridge', 'flickr', 'enabled']),
                        'clientId'     => $conf->get(['main', 'bridge', 'flickr', 'client_id']),
                        'clientSecret' => $conf->get(['main', 'bridge', 'flickr', 'client_secret']),
                    ],
                    'dailymtotion'      => [
                        'active'       => $conf->get(['main', 'bridge', 'dailymotion', 'enabled']),
                        'clientId'     => $conf->get(['main', 'bridge', 'dailymotion', 'client_id']),
                        'clientSecret' => $conf->get(['main', 'bridge', 'dailymotion', 'client_secret']),
                    ],
                ],
                'navigator'         => ['active' => $conf->get(['registry', 'api-clients', 'navigator-enabled']),],
                'office-plugin'     => ['active' => $conf->get(['registry', 'api-clients', 'office-enabled']),],
                'homepage'          => ['viewType' => $conf->get(['registry', 'general', 'home-presentation-mode']),],
                'report'            => ['anonymous' => $conf->get(['registry', 'modules', 'anonymous-report']),],
                'storage'           => ['documents' => $conf->get(['main', 'storage', 'subdefs']),],
                'searchEngine'      => [
                    'configuration' => [
                        'defaultQuery'     => $conf->get(['registry', 'searchengine', 'default-query']),
                        'defaultQueryType' => $conf->get(['registry', 'searchengine', 'default-query-type']),
                        'minChar'          => $conf->get(['registry', 'searchengine', 'min-letters-truncation']),
                    ],
                    'engine'        => [
                        'type'          => $searchEngine->getName(),
                        'status'        => $SEStatus,
                        'configuration' => $conf->get(['main', 'searchengine', 'options']),
                    ],
                ],
                'binary'            => [
                    'phpCli'      => isset($binaries['php_binary']) ? $binaries['php_binary'] : null,
                    'phpIni'      => $conf->get(['registry', 'executables', 'php-conf-path']),
                    'swfExtract'  => isset($binaries['swf_extract_binary']) ? $binaries['swf_extract_binary'] : null,
                    'pdf2swf'     => isset($binaries['pdf2swf_binary']) ? $binaries['pdf2swf_binary'] : null,
                    'swfRender'   => isset($binaries['swf_render_binary']) ? $binaries['swf_render_binary'] : null,
                    'unoconv'     => isset($binaries['unoconv_binary']) ? $binaries['unoconv_binary'] : null,
                    'ffmpeg'      => isset($binaries['ffmpeg_binary']) ? $binaries['ffmpeg_binary'] : null,
                    'ffprobe'     => isset($binaries['ffprobe_binary']) ? $binaries['ffprobe_binary'] : null,
                    'mp4box'      => isset($binaries['mp4box_binary']) ? $binaries['mp4box_binary'] : null,
                    'pdftotext'   => isset($binaries['pdftotext_binary']) ? $binaries['pdftotext_binary'] : null,
                    'pdfmaxpages' => $conf->get(['registry', 'executables', 'pdf-max-pages']),
                ],
                'mainConfiguration' => [
                    'viewBasAndCollName' => $conf->get(['registry', 'actions', 'collection-display']),
                    'chooseExportTitle'  => $conf->get(['registry', 'actions', 'export-title-choice']),
                    'defaultExportTitle' => $conf->get(['registry', 'actions', 'default-export-title']),
                    'socialTools'        => $conf->get(['registry', 'actions', 'social-tools']),
                ],
                'modules'           => [
                    'thesaurus'          => $conf->get(['registry', 'modules', 'thesaurus']),
                    'storyMode'          => $conf->get(['registry', 'modules', 'stories']),
                    'docSubsitution'     => $conf->get(['registry', 'modules', 'doc-substitution']),
                    'subdefSubstitution' => $conf->get(['registry', 'modules', 'thumb-substitution']),
                ],
                'email'             => [
                    'defaultMailAddress' => $conf->get(['registry', 'email', 'emitter-email']),
                    'smtp'               => [
                        'active'   => $conf->get(['registry', 'email', 'smtp-enabled']),
                        'auth'     => $conf->get(['registry', 'email', 'smtp-auth-enabled']),
                        'host'     => $conf->get(['registry', 'email', 'smtp-host']),
                        'port'     => $conf->get(['registry', 'email', 'smtp-port']),
                        'secure'   => $conf->get(['registry', 'email', 'smtp-secure-mode']),
                        'user'     => $conf->get(['registry', 'email', 'smtp-user']),
                        'password' => $conf->get(['registry', 'email', 'smtp-password']),
                    ],
                ],
                'ftp'               => [
                    'active'        => $conf->get(['registry', 'ftp', 'ftp-enabled']),
                    'activeForUser' => $conf->get(['registry', 'ftp', 'ftp-user-access']),
                ],
                'client'            => [
                    'maxSizeDownload'         => $conf->get(['registry', 'actions', 'download-max-size']),
                    'tabSearchMode'           => $conf->get(['registry', 'classic', 'search-tab']),
                    'tabAdvSearchPosition'    => $conf->get(['registry', 'classic', 'adv-search-tab']),
                    'tabTopicsPosition'       => $conf->get(['registry', 'classic', 'topics-tab']),
                    'tabOngActifPosition'     => $conf->get(['registry', 'classic', 'active-tab']),
                    'renderTopicsMode'        => $conf->get(['registry', 'classic', 'render-topics']),
                    'displayRolloverPreview'  => $conf->get(['registry', 'classic', 'stories-preview']),
                    'displayRolloverBasket'   => $conf->get(['registry', 'classic', 'basket-rollover']),
                    'collRenderMode'          => $conf->get(['registry', 'classic', 'collection-presentation']),
                    'viewSizeBaket'           => $conf->get(['registry', 'classic', 'basket-size-display']),
                    'clientAutoShowProposals' => $conf->get(['registry', 'classic', 'auto-show-proposals']),
                    'needAuth2DL'             => $conf->get(['registry', 'actions', 'auth-required-for-export']),
                ],
                'inscription'       => [
                    'autoSelectDB' => $conf->get(['registry', 'registration', 'auto-select-collections']),
                    'autoRegister' => $conf->get(['registry', 'registration', 'auto-register-enabled']),
                ],
                'push'              => [
                    'validationReminder' => $conf->get(['registry', 'actions', 'validation-reminder-days']),
                    'expirationValue'    => $conf->get(['registry', 'actions', 'validation-expiration-days']),
                ],
            ],
        ];
    }

    public function showPhraseanetConfigurationAction(Request $request)
    {
        $ret = array_merge(
            $this->getConfigInformation(),
            $this->getCacheInformation(),
            $this->getGlobalValuesInformation()
        );

        return Result::create($request, $ret)->createResponse();
    }

    public function listDataboxesAction(Request $request)
    {
        return Result::create($request, ["databoxes" => $this->listDataboxes()])->createResponse();
    }

    private function listDataboxes()
    {
        return array_map(function (\databox $databox) {
            return $this->listDatabox($databox);
        }, $this->getApplicationBox()->get_databoxes());
    }

    private function listDatabox(\databox $databox)
    {
        return [
            'databox_id' => $databox->get_sbas_id(),
            'name'       => $databox->get_dbname(),
            'viewname'   => $databox->get_viewname(),
            'labels'     => [
                'en' => $databox->get_label('en'),
                'de' => $databox->get_label('de'),
                'fr' => $databox->get_label('fr'),
                'nl' => $databox->get_label('nl'),
            ],
            'version'    => $databox->get_version(),
        ];
    }

    public function getDataboxCollectionAction(Request $request, $base_id)
    {
        try {
            $collection = $this->getApplicationBox()->get_collection($base_id);
        } catch (\RuntimeException $exception) {
            throw new \HttpException('Collection not found', 404, $exception);
        }

        return Result::create($request, [
            'collection' => $this->listCollection($collection),
        ])->createResponse();
    }

    /**
     * Get a Response containing the collections of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function getDataboxCollectionsAction(Request $request, $databox_id)
    {
        $ret = [
            "collections" => $this->listDataboxCollections($this->findDataboxById($databox_id)),
        ];

        return Result::create($request, $ret)->createResponse();
    }

    private function listDataboxCollections(\databox $databox)
    {
        return array_map(function (\collection $collection) {
            return $this->listCollection($collection);
        }, $databox->get_collections());
    }

    private function listCollection(\collection $collection)
    {
        $userQuery = new \User_Query($this->app);
        $orderMasters = $userQuery->on_base_ids([ $collection->get_base_id() ] )
            ->who_have_right([\ACL::ORDER_MASTER])
            ->execute()
            ->get_results()
            ->map(function (User $user) {
                return $user->getEmail();
            })
            ->toArray();

        return [
            'base_id' => $collection->get_base_id(),
            'databox_id' => $collection->get_sbas_id(),
            'collection_id' => $collection->get_coll_id(),
            'name' => $collection->get_name(),
            'labels' => [
                'fr' => $collection->get_label('fr'),
                'en' => $collection->get_label('en'),
                'de' => $collection->get_label('de'),
                'nl' => $collection->get_label('nl'),
            ],
            'record_amount' => $collection->get_record_amount(),
            'order_managers' => $orderMasters
        ];
    }

    /**
     * Get a Response containing the status of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function getDataboxStatusAction(Request $request, $databox_id)
    {
        $ret = ["status" => $this->listDataboxStatus($this->findDataboxById($databox_id)->getStatusStructure())];

        return Result::create($request, $ret)->createResponse();
    }

    private function listDataboxStatus(StatusStructure $statusStructure)
    {
        $ret = [];
        foreach ($statusStructure as $bit => $status) {
            $ret[] = [
                'bit' => $bit,
                'label_on' => $status['labelon'],
                'label_off' => $status['labeloff'],
                'labels' => [
                    'en' => $status['labels_on_i18n']['en'],
                    'fr' => $status['labels_on_i18n']['fr'],
                    'de' => $status['labels_on_i18n']['de'],
                    'nl' => $status['labels_on_i18n']['nl'],
                ],
                'img_on' => $status['img_on'],
                'img_off' => $status['img_off'],
                'searchable' => (bool) $status['searchable'],
                'printable' => (bool) $status['printable'],
            ];
        }

        return $ret;
    }

    /**
     * @param Request $request
     * @param int     $databox_id
     * @return Response
     */
    public function getDataboxMetadataAction(Request $request, $databox_id)
    {
        $ret = [
            "document_metadatas" => $this->listDataboxMetadataFields(
                $this->findDataboxById($databox_id)->get_meta_structure()
            ),
        ];

        return Result::create($request, $ret)->createResponse();
    }

    private function listDataboxMetadataFields(\databox_descriptionStructure $meta_struct)
    {
        return array_map(function ($meta) {
            return $this->listDataboxMetadataFieldProperties($meta);
        }, iterator_to_array($meta_struct));
    }

    private function listDataboxMetadataFieldProperties(\databox_field $databox_field)
    {
        return ['id'               => $databox_field->get_id(),
                'namespace'        => $databox_field->get_tag()->getGroupName(),
                'source'           => $databox_field->get_tag()->getTagname(),
                'tagname'          => $databox_field->get_tag()->getName(),
                'name'             => $databox_field->get_name(),
                'labels'           => [
                    'fr' => $databox_field->get_label('fr'),
                    'en' => $databox_field->get_label('en'),
                    'de' => $databox_field->get_label('de'),
                    'nl' => $databox_field->get_label('nl'),
                ],
                'separator'        => $databox_field->get_separator(),
                'thesaurus_branch' => $databox_field->get_tbranch(),
                'type'             => $databox_field->get_type(),
                'indexable'        => $databox_field->is_indexable(),
                'multivalue'       => $databox_field->is_multi(),
                'readonly'         => $databox_field->is_readonly(),
                'required'         => $databox_field->is_required(),
        ];
    }

    /**
     * Get a Response containing the terms of use of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function getDataboxTermsAction(Request $request, $databox_id)
    {
        $ret = ["termsOfUse" => $this->listDataboxTerms($this->findDataboxById($databox_id))];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Retrieve CGU's for the specified \databox
     *
     * @param  \databox $databox
     *
     * @return array
     */
    private function listDataboxTerms(\databox $databox)
    {
        $ret = [];
        foreach ($databox->get_cgus() as $locale => $array_terms) {
            $ret[] = ['locale' => $locale, 'terms' => $array_terms['value']];
        }

        return $ret;
    }

    public function listQuarantineAction(Request $request)
    {
        $offset_start = max($request->get('offset_start', 0), 0);
        $per_page = min(max($request->get('per_page', 10), 1), 1000);

        $baseIds = array_keys($this->getAclForUser()->get_granted_base(['canaddrecord']));

        $lazaretFiles = [];

        if (count($baseIds) > 0) {
            /** @var LazaretFileRepository $lazaretRepository */
            $lazaretRepository = $this->app['repo.lazaret-files'];
            $lazaretFiles = iterator_to_array($lazaretRepository->findPerPage($baseIds, $offset_start, $per_page));
        }

        $ret = array_map(function ($lazaretFile) {
            return $this->listLazaretFile($lazaretFile);
        }, $lazaretFiles);

        $ret = ['offset_start' => $offset_start, 'per_page' => $per_page, 'quarantine_items' => $ret,];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @param int     $lazaret_id
     * @param Request $request
     * @return Response
     */
    public function listQuarantineItemAction($lazaret_id, Request $request)
    {
        /** @var LazaretFileRepository $repository */
        $repository = $this->app['repo.lazaret-files'];
        /** @var LazaretFile $lazaretFile */
        $lazaretFile = $repository->find($lazaret_id);

        if (null === $lazaretFile) {
            return Result::createError($request, 404, sprintf('Lazaret file id %d not found', $lazaret_id))->createResponse();
        }

        if (!$this->getAclForUser()->has_right_on_base($lazaretFile->getBaseId(), 'canaddrecord')) {
            return Result::createError($request, 403, 'You do not have access to this quarantine item')->createResponse();
        }

        $ret = ['quarantine_item' => $this->listLazaretFile($lazaretFile)];

        return Result::create($request, $ret)->createResponse();
    }

    private function listLazaretFile(LazaretFile $file)
    {
        $manager = $this->getBorderManager();
        /** @var TranslatorInterface $translator */
        $translator = $this->app['translator'];

        $checks = array_map(function (LazaretCheck $checker) use ($manager, $translator) {
            $checkerFQCN = $checker->getCheckClassname();
            return $manager->getCheckerFromFQCN($checkerFQCN)->getMessage($translator);
        }, iterator_to_array($file->getChecks()));

        $usr_id = $user = null;
        if ($file->getSession()->getUser()) {
            $user = $file->getSession()->getUser();
            $usr_id = $user->getId();
        }

        $session = [
            'id' => $file->getSession()->getId(),
            'usr_id' => $usr_id,
            'user' => $user ? $this->listUser($user) : null,
        ];

        return [
            'id'                 => $file->getId(),
            'quarantine_session' => $session,
            'base_id'            => $file->getBaseId(),
            'original_name'      => $file->getOriginalName(),
            'sha256'             => $file->getSha256(),
            'uuid'               => $file->getUuid(),
            'forced'             => $file->getForced(),
            'checks'             => $file->getForced() ? [] : $checks,
            'created_on'         => $file->getCreated()->format(DATE_ATOM),
            'updated_on'         => $file->getUpdated()->format(DATE_ATOM),
        ];
    }

    private function listUser(User $user)
    {
        switch ($user->getGender()) {
            case User::GENDER_MRS:
                $gender = 'Mrs';
                break;
            case User::GENDER_MISS:
                $gender = 'Miss';
                break;
            case User::GENDER_MR:
            default:
                $gender = 'Mr';
        }

        return [
            '@entity@'        => self::OBJECT_TYPE_USER,
            'id'              => $user->getId(),
            'email'           => $user->getEmail() ?: null,
            'login'           => $user->getLogin() ?: null,
            'first_name'      => $user->getFirstName() ?: null,
            'last_name'       => $user->getLastName() ?: null,
            'display_name'    => $user->getDisplayName() ?: null,
            'gender'          => $gender,
            'address'         => $user->getAddress() ?: null,
            'zip_code'        => $user->getZipCode() ?: null,
            'city'            => $user->getCity() ?: null,
            'country'         => $user->getCountry() ?: null,
            'phone'           => $user->getPhone() ?: null,
            'fax'             => $user->getFax() ?: null,
            'job'             => $user->getJob() ?: null,
            'position'        => $user->getActivity() ?: null,
            'company'         => $user->getCompany() ?: null,
            'geoname_id'      => $user->getGeonameId() ?: null,
            'last_connection' => NullableDateTime::format($user->getLastConnection()),
            'created_on'      => NullableDateTime::format($user->getCreated()),
            'updated_on'      => NullableDateTime::format($user->getUpdated()),
            'locale'          => $user->getLocale() ?: null,
        ];
    }

    private function listUserCollections(User $user)
    {
        $acl = $this->getAclForUser($user);
        $rights = $acl->get_bas_rights();
        $bases = $acl->get_granted_base();

        $grants = [];


        $statusMapper = new RestrictedStatusExtractor($acl, $this->getApplicationBox());

        foreach ($bases as $base) {
            $baseGrants = [];

            foreach ($rights as $right) {
                if (! $acl->has_right_on_base($base->get_base_id(), $right)) {
                    continue;
                }

                $baseGrants[] = $right;
            }

            $grants[] = [
                'databox_id' => $base->get_sbas_id(),
                'base_id' => $base->get_base_id(),
                'collection_id' => $base->get_coll_id(),
                'rights' => $baseGrants,
                'statuses' => $statusMapper->getRestrictedStatuses($base->get_base_id())
            ];
        }

        return $grants;
    }

    private function listUserDataboxes(User $user)
    {
        $acl = $this->getAclForUser($user);
        $rightsByDatabox = $acl->get_sbas_rights();
        $grants = [];

        foreach ($rightsByDatabox as $databoxId => $databoxRights) {
            $rights = [];

            foreach ($databoxRights as $name => $allowedFlag) {
                if (! $allowedFlag) {
                    continue;
                }

                $rights[] = $name;
            }

            $grants[] = [
                'databox_id' => $databoxId,
                'rights' => $rights
            ];
        }

        return $grants;
    }

    private function listUserDemands(User $user)
    {
        return (new CollectionRequestMapper($this->app, $this->app['registration.manager']))->getUserRequests($user);
    }

    public function requestPasswordReset(Request $request, $email)
    {
        /** @var \Alchemy\Phrasea\Authentication\RecoveryService $service */
        $service = $this->app['authentication.recovery_service'];

        try {
            $token = $service->requestPasswordResetToken($email, false);
        }
        catch (\Exception $exception) {
            $token = $service->requestPasswordResetTokenByLogin($email, false);
        }

        return Result::create($request, [ 'reset_token' => $token ])->createResponse();
    }

    public function resetPassword(Request $request, $token)
    {
        $password = $request->request->get('password', null);
        /** @var \Alchemy\Phrasea\Authentication\RecoveryService $service */
        $service = $this->app['authentication.recovery_service'];

        try {
            $service->resetPassword($token, $password);
        }
        catch (\Exception $exception) {
            return Result::create($request, [ 'success' => false ])->createResponse();
        }

        return Result::create($request, [ 'success' => true ])->createResponse();
    }

    public function updatePassword(Request $request, $login)
    {
        $service = $this->getAccountService();
        $command = new UpdatePasswordCommand();
        $form = $this->app->form(new PhraseaRenewPasswordForm(), $command, [
            'csrf_protection' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $service->updatePassword($command, $login);

                return Result::create($request, [ 'success' => true ]);
            }
            catch (AccountException $exception) {
                return Result::create($request, [ 'success' => false, 'message' => $exception->getMessage() ]);
            }
        }

        return Result::create($request, [ 'success' => false, 'message' => (string) $form->getErrors() ]);
    }

    public function unlockAccount(Request $request, $token)
    {
        try {
            $this->getRegistrationService()->unlockAccount($token);
        }
        catch (RegistrationException $exception) {
            return Result::createError($request, 400, $exception->getMessage())->createResponse();
        }

        return Result::create($request, [ 'success' => true ])->createResponse();
    }

    public function addRecordAction(Request $request)
    {
        if (count($request->files->get('file')) == 0) {
            return $this->getBadRequestAction($request, 'Missing file parameter');
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->getBadRequestAction($request, 'You can upload one file at time');
        }

        if (!$file->isValid()) {
            return $this->getBadRequestAction($request, 'Data corrupted, please try again');
        }

        if (!$request->get('base_id')) {
            return $this->getBadRequestAction($request, 'Missing base_id parameter');
        }

        $collection = \collection::getByBaseId($this->app, $request->get('base_id'));

        if (!$this->getAclForUser()->has_right_on_base($request->get('base_id'), 'canaddrecord')) {
            return Result::createError($request, 403, sprintf(
                'You do not have access to collection %s', $collection->get_label($this->app['locale'])
            ))->createResponse();
        }

        $media = $this->app->getMediaFromUri($file->getPathname());

        $Package = new File($this->app, $media, $collection, $file->getClientOriginalName());

        if ($request->get('status')) {
            $Package->addAttribute(new Status($this->app, $request->get('status')));
        }

        $session = new LazaretSession();
        $session->setUser($this->getAuthenticatedUser());

        $entityManager = $this->app['orm.em'];
        $entityManager->persist($session);
        $entityManager->flush();

        $reasons = $output = null;

        $translator = $this->app['translator'];
        $callback = function ($element, Visa $visa) use ($translator, &$reasons, &$output) {
            if (!$visa->isValid()) {
                $reasons = array_map(function (CheckerResponse $response) use ($translator) {
                    return $response->getMessage($translator);
                }, $visa->getResponses());
            }

            $output = $element;
        };

        switch ($request->get('forceBehavior')) {
            case '0' :
                $behavior = Manager::FORCE_RECORD;
                break;
            case '1' :
                $behavior = Manager::FORCE_LAZARET;
                break;
            case null:
                $behavior = null;
                break;
            default:
                return $this->getBadRequestAction($request, sprintf(
                    'Invalid forceBehavior value `%s`', $request->get('forceBehavior')
                ));
        }

        $nosubdef = $request->get('nosubdefs') === '' || \p4field::isyes($request->get('nosubdefs'));
        $this->getBorderManager()->process($session, $Package, $callback, $behavior, $nosubdef);

        $ret = ['entity' => null];

        if ($output instanceof \record_adapter) {
            $ret['entity'] = '0';
            $ret['url'] = '/records/' . $output->getDataboxId() . '/' . $output->getRecordId() . '/';
            $this->dispatch(PhraseaEvents::RECORD_UPLOAD, new RecordEdit($output));
        }
        if ($output instanceof LazaretFile) {
            $ret['entity'] = '1';
            $ret['url'] = '/quarantine/item/' . $output->getId() . '/';
        }

        return Result::create($request, $ret)->createResponse();
    }

    public function substituteAction(Request $request)
    {
        $ret = array();

        if (count($request->files->get('file')) == 0) {
            return $this->getBadRequestAction($request, 'Missing file parameter');
        }
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->getBadRequestAction($request, 'You can upload one file at time');
        }

        if (!$file->isValid()) {
            return $this->getBadRequestAction($request, 'Data corrupted, please try again');
        }
        if (!$request->get('databox_id')) {
            $this->getBadRequestAction($request, 'Missing databox_id parameter');
        }
        if (!$request->get('record_id')) {
            $this->getBadRequestAction($request, 'Missing record_id parameter');
        }
        if (!$request->get('name')) {
            return $this->getBadRequestAction($request, 'Missing name parameter');
        }

        $media = $this->app->getMediaFromUri($file->getPathname());
        $record = $this->findDataboxById($request->get('databox_id'))->get_record($request->get('record_id'));
        $base_id = $record->getBaseId();
        $collection = \collection::getByBaseId($this->app, $base_id);
        if (!$this->getAclForUser()->has_right_on_base($base_id, 'canaddrecord')) {
            return Result::createError($request, 403, sprintf(
                'You do not have access to collection %s', $collection->get_label($this->app['locale.I18n'])
            ));
        }
        $adapt = ($request->get('adapt')===null || !(\p4field::isno($request->get('adapt'))));
        $ret['adapt'] = $adapt;
        $this->getSubdefSubstituer()->substitute($record, $request->get('name'), $media, $adapt);
        foreach ($record->get_embedable_medias() as $name => $media) {
            if ($name == $request->get('name') &&
                null !== ($subdef = $this->listEmbeddableMedia($request, $record, $media))) {
                $ret[] = $subdef;
            }
        }

        return Result::create($request, $ret)->createResponse();
    }

    private function listEmbeddableMedia(Request $request, \record_adapter $record, \media_subdef $media)
    {
        if (!$media->is_physically_present()) {
            return null;
        }

        if ($this->getAuthenticator()->isAuthenticated()) {
            $acl = $this->getAclForUser();
            if ($media->get_name() !== 'document'
                && false === $acl->has_access_to_subdef($record, $media->get_name())
            ) {
                return null;
            }
            if ($media->get_name() === 'document'
                && !$acl->has_right_on_base($record->getBaseId(), \ACL::CANDWNLDHD)
                && !$acl->has_hd_grant($record)
            ) {
                return null;
            }
        }

        if ($media->get_permalink() instanceof \media_Permalink_Adapter) {
            $permalink = $this->listPermalink($media->get_permalink());
        } else {
            $permalink = null;
        }

        $urlTTL = (int) $request->get(
            'subdef_url_ttl',
            $this->getConf()->get(['registry', 'general', 'default-subdef-url-ttl'])
        );
        if ($urlTTL < 0) {
            $urlTTL = -1;
        }
        $issuer = $this->getAuthenticatedUser();

        return [
            'name' => $media->get_name(),
            'permalink' => $permalink,
            'height' => $media->get_height(),
            'width' => $media->get_width(),
            'filesize' => $media->get_size(),
            'devices' => $media->getDevices(),
            'player_type' => $media->get_type(),
            'mime_type' => $media->get_mime(),
            'substituted' => $media->is_substituted(),
            'created_on'  => $media->get_creation_date()->format(DATE_ATOM),
            'updated_on'  => $media->get_modification_date()->format(DATE_ATOM),
            'url' => $this->app['media_accessor.subdef_url_generator']->generate($issuer, $media, $urlTTL),
            'url_ttl' => $urlTTL,
        ];
    }

    private function listPermalink(\media_Permalink_Adapter $permalink)
    {
        $downloadUrl = $permalink->get_url();
        $downloadUrl->getQuery()->set('download', '1');

        return [
            'created_on'   => $permalink->get_created_on()->format(DATE_ATOM),
            'id'           => $permalink->get_id(),
            'is_activated' => $permalink->get_is_activated(),
            /** @Ignore */
            'label'        => $permalink->get_label(),
            'updated_on'   => $permalink->get_last_modified()->format(DATE_ATOM),
            'page_url'     => $permalink->get_page(),
            'download_url' => (string)$downloadUrl,
            'url'          => (string)$permalink->get_url(),
        ];
    }

    /**
     * Search for results
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $subdefTransformer = new SubdefTransformer($this->app['acl'], $this->getAuthenticatedUser(), new PermalinkTransformer());
        $technicalDataTransformer = new TechnicalDataTransformer();
        $recordTransformer = new RecordTransformer($subdefTransformer, $technicalDataTransformer);
        $storyTransformer = new StoryTransformer($subdefTransformer, $recordTransformer);
        $compositeTransformer = new V1SearchCompositeResultTransformer($recordTransformer, $storyTransformer);
        $searchTransformer = new V1SearchResultTransformer($compositeTransformer);

        $transformerResolver = new SearchResultTransformerResolver([
            '' => $searchTransformer,
            'results' => $compositeTransformer,
            'results.stories' => $storyTransformer,
            'results.stories.thumbnail' => $subdefTransformer,
            'results.stories.metadatas' => new CallbackTransformer(),
            'results.stories.caption' => new CallbackTransformer(),
            'results.stories.records' => $recordTransformer,
            'results.stories.records.thumbnail' => $subdefTransformer,
            'results.stories.records.technical_informations' => $technicalDataTransformer,
            'results.stories.records.subdefs' => $subdefTransformer,
            'results.stories.records.metadata' => new CallbackTransformer(),
            'results.stories.records.status' => new CallbackTransformer(),
            'results.stories.records.caption' => new CallbackTransformer(),
            'results.records' => $recordTransformer,
            'results.records.thumbnail' => $subdefTransformer,
            'results.records.technical_informations' => $technicalDataTransformer,
            'results.records.subdefs' => $subdefTransformer,
            'results.records.metadata' => new CallbackTransformer(),
            'results.records.status' => new CallbackTransformer(),
            'results.records.caption' => new CallbackTransformer(),
        ]);

        $includeResolver = new IncludeResolver($transformerResolver);

        $fractal = new \League\Fractal\Manager();
        $fractal->setSerializer(new TraceableArraySerializer($this->app['dispatcher']));
        $fractal->parseIncludes($this->resolveSearchIncludes($request));

        $result = $this->doSearch($request);
        $searchView = $this->buildSearchView(
            $result,
            $includeResolver->resolve($fractal),
            $this->resolveSubdefUrlTTL($request)
        );

        $ret = $fractal->createData(new Item($searchView, $searchTransformer))->toArray();

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Response containing the results of a records search
     *
     * @deprecated in favor of search
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchRecordsAction(Request $request)
    {
        $subdefTransformer = new SubdefTransformer($this->app['acl'], $this->getAuthenticatedUser(), new PermalinkTransformer());
        $technicalDataTransformer = new TechnicalDataTransformer();
        $recordTransformer = new RecordTransformer($subdefTransformer, $technicalDataTransformer);
        $searchTransformer = new V1SearchRecordsResultTransformer($recordTransformer);

        $transformerResolver = new SearchResultTransformerResolver([
            '' => $searchTransformer,
            'results' => $recordTransformer,
            'results.thumbnail' => $subdefTransformer,
            'results.technical_informations' => $technicalDataTransformer,
            'results.subdefs' => $subdefTransformer,
            'results.metadata' => new CallbackTransformer(),
            'results.status' => new CallbackTransformer(),
            'results.caption' => new CallbackTransformer(),
        ]);
        $includeResolver = new IncludeResolver($transformerResolver);

        $fractal = new \League\Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $fractal->parseIncludes($this->resolveSearchRecordsIncludes($request));

        $searchView = $this->buildSearchRecordsView(
            $this->doSearch($request),
            $includeResolver->resolve($fractal),
            $this->resolveSubdefUrlTTL($request)
        );

        $ret = $fractal->createData(new Item($searchView, $searchTransformer))->toArray();

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @param SearchEngineResult $result
     * @param string[] $includes
     * @param int $urlTTL
     * @return SearchResultView
     */
    private function buildSearchView(SearchEngineResult $result, array $includes, $urlTTL)
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

            foreach ($stories->getDataboxIds() as $databoxId) {
                $storyIds = $stories->getDataboxRecordIds($databoxId);

                $selections = $this->findDataboxById($databoxId)
                    ->getRecordRepository()
                    ->findChildren($storyIds, $user);
                $children[$databoxId] = array_combine($storyIds, $selections);
            }

            /** @var StoryView[] $storyViews */
            $storyViews = [];
            /** @var RecordView[] $childrenViews */
            $childrenViews = [];

            foreach ($stories as $index => $story) {
                $storyView = new StoryView($story);

                $selection = $children[$story->getDataboxId()][$story->getRecordId()];

                $childrenView = $this->buildRecordViews($selection);

                foreach ($childrenView as $view) {
                    $childrenViews[spl_object_hash($view)] = $view;
                }

                $storyView->setChildren($childrenView);

                $storyViews[$index] = $storyView;
            }

            if (in_array('results.stories.thumbnail', $includes, true)) {
                $subdefViews = $this->buildSubdefsViews($stories, ['thumbnail'], $urlTTL);

                foreach ($storyViews as $index => $storyView) {
                    $storyView->setSubdefs($subdefViews[$index]);
                }
            }

            if (in_array('results.stories.metadatas', $includes, true) ||
                in_array('results.stories.caption', $includes, true)) {
                $captions = $this->app['service.caption']->findByReferenceCollection($stories);
                $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($stories);

                $this->buildCaptionViews($storyViews, $captions, $canSeeBusiness);
            }

            $allChildren = new RecordCollection();
            foreach ($childrenViews as $index => $childrenView) {
                $allChildren[$index] = $childrenView->getRecord();
            }

            $names = in_array('results.stories.records.subdefs', $includes, true) ? null : ['thumbnail'];
            $subdefViews = $this->buildSubdefsViews($allChildren, $names, $urlTTL);
            $technicalDatasets = $this->app['service.technical_data']->fetchRecordsTechnicalData($allChildren);

            foreach ($childrenViews as $index => $recordView) {
                $recordView->setSubdefs($subdefViews[$index]);
                $recordView->setTechnicalDataView(new TechnicalDataView($technicalDatasets[$index]));
            }

            if (array_intersect($includes, ['results.stories.records.metadata', 'results.stories.records.caption'])) {
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
     * @param SearchEngineResult $result
     * @param string[] $includes
     * @param int $urlTTL
     * @return SearchResultView
     */
    private function buildSearchRecordsView(SearchEngineResult $result, array $includes, $urlTTL)
    {
        $references = new RecordReferenceCollection($result->getResults());
        $references = new RecordCollection($references->toRecords($this->getApplicationBox()));

        $names = in_array('results.subdefs', $includes, true) ? null : ['thumbnail'];

        $recordViews = $this->buildRecordViews($references);
        $subdefViews = $this->buildSubdefsViews($references, $names, $urlTTL);
        $technicalDatasets = $this->app['service.technical_data']->fetchRecordsTechnicalData($references);

        foreach ($recordViews as $index => $recordView) {
            $recordView->setSubdefs($subdefViews[$index]);
            $recordView->setTechnicalDataView(new TechnicalDataView($technicalDatasets[$index]));
        }

        if (array_intersect($includes, ['results.metadata', 'results.caption'])) {
            $captions = $this->app['service.caption']->findByReferenceCollection($references);
            $canSeeBusiness = $this->retrieveSeeBusinessPerDatabox($references);

            $this->buildCaptionViews($recordViews, $captions, $canSeeBusiness);
        }

        $resultView = new SearchResultView($result);
        $resultView->setRecords($recordViews);

        return $resultView;
    }

    /**
     * @param RecordReferenceInterface[]|RecordReferenceCollection|DataboxGroupable $references
     * @param array|null $names
     * @param int $urlTTL
     * @return SubdefView[][]
     */
    private function buildSubdefsViews($references, array $names = null, $urlTTL)
    {
        $subdefGroups = $this->app['service.media_subdef']
            ->findSubdefsByRecordReferenceFromCollection($references, $names);

        $fakeSubdefs = [];

        foreach ($subdefGroups as $index => $subdefGroup) {
            if (!isset($subdefGroup['thumbnail'])) {
                $fakeSubdef = new \media_subdef($this->app, $references[$index], 'thumbnail', true, []);
                $fakeSubdefs[spl_object_hash($fakeSubdef)] = $fakeSubdef;

                $subdefGroups[$index]['thumbnail'] = $fakeSubdef;
            }
        }

        $allSubdefs = $this->mergeGroupsIntoOneList($subdefGroups);
        $allPermalinks = \media_Permalink_Adapter::getMany(
            $this->app,
            array_filter($allSubdefs, function (\media_subdef $subdef) use ($fakeSubdefs) {
                return !isset($fakeSubdefs[spl_object_hash($subdef)]);
            })
        );
        $urls = $this->app['media_accessor.subdef_url_generator']
            ->generateMany($this->getAuthenticatedUser(), $allSubdefs, $urlTTL);

        $subdefViews = [];

        /** @var \media_subdef $subdef */
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

        /** @var \media_subdef[] $subdefGroup */
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
     * Returns requested includes
     *
     * @param Request $request
     * @return string[]
     */
    private function resolveSearchIncludes(Request $request)
    {
        $includes = [
            'results.stories.records'
        ];

        if ($request->attributes->get('_extended', false)) {
            if ($request->get('search_type') != SearchEngineOptions::RECORD_STORY) {
                $includes = array_merge($includes, [
                    'results.stories.records.subdefs',
                    'results.stories.records.metadata',
                    'results.stories.records.caption',
                    'results.stories.records.status'
                ]);
            }
            else {
                $includes = [ 'results.stories.caption' ];
            }

            $includes = array_merge($includes, [
                'results.records.subdefs',
                'results.records.metadata',
                'results.records.caption',
                'results.records.status'
            ]);
        }

        return $includes;
    }

    /**
     * Returns requested includes
     *
     * @param Request $request
     * @return string[]
     */
    private function resolveSearchRecordsIncludes(Request $request)
    {
        if ($request->attributes->get('_extended', false)) {
            return [
                'results.subdefs',
                'results.metadata',
                'results.caption',
                'results.status'
            ];
        }

        return [];
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
     * @param Request $request
     * @return SearchEngineResult
     */
    private function doSearch(Request $request)
    {
        $options = SearchEngineOptions::fromRequest($this->app, $request);
        $options->setFirstResult((int)($request->get('offset_start') ?: 0));
        $options->setMaxResults((int)$request->get('per_page') ?: 10);

        $this->getSearchEngine()->resetCache();

        $search_result = $this->getSearchEngine()->query((string)$request->get('query'), $options);

        $this->getUserManipulator()->logQuery($this->getAuthenticatedUser(), $search_result->getUserQuery());

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function (\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $this->getSearchEngineLogger()
                ->log($databox, $search_result->getUserQuery(), $search_result->getTotal(), $colls);
        }

        $this->getSearchEngine()->clearCache();

        return $search_result;
    }

    /**
     * @param Request $request
     * @param RecordReferenceInterface[]|RecordReferenceCollection $records
     * @return array
     */
    private function listRecords(Request $request, $records)
    {
        if (!$records instanceof RecordReferenceCollection) {
            $records = new RecordReferenceCollection($records);
        }

        $technicalData = $this->app['service.technical_data']->fetchRecordsTechnicalData($records);

        $data = [];

        foreach ($records->toRecords($this->getApplicationBox()) as $index => $record) {
            $record->setTechnicalDataSet($technicalData[$index]);

            $data[$index] = $this->listRecord($request, $record);
        }

        return $data;
    }

    /**
     * Retrieve detailed information about one record
     *
     * @param Request          $request
     * @param \record_adapter $record
     * @return array
     */
    private function listRecord(Request $request, \record_adapter $record)
    {
        $technicalInformation = [];
        foreach ($record->get_technical_infos()->getValues() as $name => $value) {
            $technicalInformation[] = ['name' => $name, 'value' => $value];
        }

        $data = [
            'databox_id'             => $record->getDataboxId(),
            'record_id'              => $record->getRecordId(),
            'mime_type'              => $record->getMimeType(),
            'title'                  => $record->get_title(),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->getUpdated()->format(DATE_ATOM),
            'created_on'             => $record->getCreated()->format(DATE_ATOM),
            'collection_id'          => $record->getCollectionId(),
            'base_id'                => $record->getBaseId(),
            'sha256'                 => $record->getSha256(),
            'thumbnail'              => $this->listEmbeddableMedia($request, $record, $record->get_thumbnail()),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->getType(),
            'uuid'                   => $record->getUuid(),
        ];

        if ($request->attributes->get('_extended', false)) {
            $data = array_merge($data, [
                'subdefs' => $this->listRecordEmbeddableMedias($request, $record),
                'metadata' => $this->listRecordMetadata($record),
                'status' => $this->listRecordStatus($record),
                'caption' => $this->listRecordCaption($record),
            ]);
        }

        return $data;
    }

    /**
     * Retrieve detailed information about one story
     *
     * @param Request         $request
     * @param \record_adapter $story
     * @return array
     * @throws \Exception
     */
    private function listStory(Request $request, \record_adapter $story)
    {
        if (!$story->isStory()) {
            return Result::createError($request, 404, 'Story not found')->createResponse();
        }

        $caption = $story->get_caption();

        $format = function (\caption_record $caption, $dcField) {

            $field = $caption->get_dc_field($dcField);

            if (!$field) {
                return null;
            }

            return $field->get_serialized_values();
        };

        return [
            '@entity@'      => self::OBJECT_TYPE_STORY,
            'databox_id'    => $story->getDataboxId(),
            'story_id'      => $story->getRecordId(),
            'updated_on'    => $story->getUpdated()->format(DATE_ATOM),
            'created_on'    => $story->getCreated()->format(DATE_ATOM),
            'collection_id' => $story->getCollectionId(),
            'base_id'       => $story->getBaseId(),
            'thumbnail'     => $this->listEmbeddableMedia($request, $story, $story->get_thumbnail()),
            'uuid'          => $story->getUuid(),
            'metadatas'     => [
                '@entity@'       => self::OBJECT_TYPE_STORY_METADATA_BAG,
                'dc:contributor' => $format($caption, \databox_Field_DCESAbstract::Contributor),
                'dc:coverage'    => $format($caption, \databox_Field_DCESAbstract::Coverage),
                'dc:creator'     => $format($caption, \databox_Field_DCESAbstract::Creator),
                'dc:date'        => $format($caption, \databox_Field_DCESAbstract::Date),
                'dc:description' => $format($caption, \databox_Field_DCESAbstract::Description),
                'dc:format'      => $format($caption, \databox_Field_DCESAbstract::Format),
                'dc:identifier'  => $format($caption, \databox_Field_DCESAbstract::Identifier),
                'dc:language'    => $format($caption, \databox_Field_DCESAbstract::Language),
                'dc:publisher'   => $format($caption, \databox_Field_DCESAbstract::Publisher),
                'dc:relation'    => $format($caption, \databox_Field_DCESAbstract::Relation),
                'dc:rights'      => $format($caption, \databox_Field_DCESAbstract::Rights),
                'dc:source'      => $format($caption, \databox_Field_DCESAbstract::Source),
                'dc:subject'     => $format($caption, \databox_Field_DCESAbstract::Subject),
                'dc:title'       => $format($caption, \databox_Field_DCESAbstract::Title),
                'dc:type'        => $format($caption, \databox_Field_DCESAbstract::Type),
            ],
            'records'       => $this->listRecords($request, array_values($story->getChildren()->get_elements())),
        ];
    }

    /**
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     * @return Response
     */
    public function getRecordCaptionAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);
        $fields = $record->get_caption()->get_fields();

        $ret = [
            'caption_metadatas' => array_map(function (\caption_field $field) {
                return [
                    'meta_structure_id' => $field->get_meta_struct_id(),
                    'name'              => $field->get_name(),
                    'value'             => $field->get_serialized_values(";"),
                ];
            }, $fields),
        ];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Response containing the record metadata
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return Response
     */
    public function getRecordMetadataAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);
        $ret = ["record_metadatas" => $this->listRecordMetadata($record)];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * List all fields of given record
     *
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordMetadata(\record_adapter $record)
    {
        $includeBusiness = $this->getAclForUser()->can_see_business_fields($record->getDatabox());

        return $this->listRecordCaptionFields($record->get_caption()->get_fields(null, $includeBusiness));
    }

    /**
     * @param \caption_field[] $fields
     * @return array
     */
    private function listRecordCaptionFields($fields)
    {
        $ret = [];

        foreach ($fields as $field) {
            $databox_field = $field->get_databox_field();

            $fieldData = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'labels' => [
                    'fr' => $databox_field->get_label('fr'),
                    'en' => $databox_field->get_label('en'),
                    'de' => $databox_field->get_label('de'),
                    'nl' => $databox_field->get_label('nl'),
                ],
            ];

            foreach ($field->get_values() as $value) {
                $data = [
                    'meta_id' => $value->getId(),
                    'value' => $value->getValue(),
                ];

                $ret[] = $fieldData + $data;
            }
        }

        return $ret;
    }

    /**
     * Get a Response containing the record status
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return Response
     */
    public function getRecordStatusAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        $ret = ["status" => $this->listRecordStatus($record)];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Retrieve detailed information about one status
     *
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordStatus(\record_adapter $record)
    {
        $ret = [];
        foreach ($record->getStatusStructure() as $bit => $status) {
            $ret[] = [
                'bit'   => $bit,
                'state' => \databox_status::bitIsSet($record->getStatusBitField(), $bit),
            ];
        }

        return $ret;
    }

    /**
     * Get a Response containing the baskets where the record is in
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return Response
     */
    public function getRelatedRecordsAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        $baskets = array_map(function (Basket $basket) {
            return $this->listBasket($basket);
        }, (array) $record->get_container_baskets($this->app['orm.em'], $this->getAuthenticatedUser()));


        $stories = array_map(function (\record_adapter $story) use ($request) {
            return $this->listStory($request, $story);
        }, array_values($record->get_grouping_parents()->get_elements()));

        return Result::create($request, ["baskets" => $baskets, "stories" => $stories])->createResponse();
    }

    /**
     * Retrieve information about one basket
     *
     * @param  Basket $basket
     *
     * @return array
     */
    private function listBasket(Basket $basket)
    {
        $ret = [
            'basket_id' => $basket->getId(),
            'owner' => $this->listUser($basket->getUser()),
            'created_on' => $basket->getCreated()->format(DATE_ATOM),
            'description' => (string) $basket->getDescription(),
            'name' => $basket->getName(),
            'pusher_usr_id'     => $basket->getPusher() ? $basket->getPusher()->getId() : null,
            'pusher'            => $basket->getPusher() ? $this->listUser($basket->getPusher()) : null,
            'updated_on'        => $basket->getUpdated()->format(DATE_ATOM),
            'unread'            => !$basket->isRead(),
            'validation_basket' => !!$basket->getValidation(),
        ];

        if ($basket->getValidation()) {
            $users = array_map(function (ValidationParticipant $participant) {
                $user = $participant->getUser();

                return [
                    'usr_id' => $user->getId(),
                    'usr_name' => $user->getDisplayName(),
                    'confirmed' => $participant->getIsConfirmed(),
                    'can_agree' => $participant->getCanAgree(),
                    'can_see_others' => $participant->getCanSeeOthers(),
                    'readonly' => $user->getId() != $this->getAuthenticatedUser()->getId(),
                    'user' => $this->listUser($user),
                ];
            }, iterator_to_array($basket->getValidation()->getParticipants()));

            $expires_on_atom = NullableDateTime::format($basket->getValidation()->getExpires());

            $ret = array_merge([
                'validation_users'          => $users,
                'expires_on'                => $expires_on_atom,
                'validation_infos'          => $basket->getValidation()
                    ->getValidationString($this->app, $this->getAuthenticatedUser()),
                'validation_confirmed'      => $basket->getValidation()
                    ->getParticipant($this->getAuthenticatedUser())
                    ->getIsConfirmed(),
                'validation_initiator'      => $basket->getValidation()
                    ->isInitiator($this->getAuthenticatedUser()),
                'validation_initiator_user' => $this->listUser($basket->getValidation()->getInitiator()),
            ], $ret);
        }

        return $ret;
    }

    /**
     * Get a Response containing the record embed files
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return Response
     */
    public function getEmbeddedRecordAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        $devices = $request->get('devices', []);
        $mimes = $request->get('mimes', []);

        $ret = array_values(array_filter(array_map(function ($media) use ($request, $record) {
            return $this->listEmbeddableMedia($request, $record, $media);
        }, $record->get_embedable_medias($devices, $mimes))));

        return Result::create($request, ["embed" => $ret])->createResponse();
    }

    public function setRecordMetadataAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);
        $metadata = $request->get('metadatas');

        if (!is_array($metadata)) {
            return $this->getBadRequestAction($request, 'Metadatas should be an array');
        }

        foreach ($metadata as $item) {
            if (!is_array($item)) {
                return $this->getBadRequestAction($request, 'Each Metadata value should be an array');
            }
        }

        $record->set_metadatas($metadata);

        return Result::create($request, [
            "record_metadatas" => $this->listRecordMetadata($record),
        ])->createResponse();
    }

    /**
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     * @return Response
     */
    public function setRecordStatusAction(Request $request, $databox_id, $record_id)
    {
        $databox = $this->findDataboxById($databox_id);
        $record = $databox->get_record($record_id);
        $statusStructure = $databox->getStatusStructure();

        $status = $request->get('status');

        $datas = strrev($record->getStatus());

        if (!is_array($status)) {
            return $this->getBadRequestAction($request);
        }
        foreach ($status as $n => $value) {
            if ($n > 31 || $n < 4) {
                return $this->getBadRequestAction($request);
            }
            if (!in_array($value, ['0', '1'])) {
                return $this->getBadRequestAction($request);
            }
            if (!$statusStructure->hasStatus($n)) {
                return $this->getBadRequestAction($request);
            }

            $datas = substr($datas, 0, ($n)) . $value . substr($datas, ($n + 2));
        }

        $record->setStatus(strrev($datas));

        // @todo Move event dispatch inside record_adapter class (keeps things encapsulated)
        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record));

        $ret = ["status" => $this->listRecordStatus($record)];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     * @return Response
     */
    public function deleteRecordAction(Request $request, $databox_id, $record_id)
    {
        $databox = $this->findDataboxById($databox_id);
        $record = $databox->get_record($record_id);

        $record->delete();

        return Result::create($request, [])->createResponse();
    }

    /**
     * Return detailed information about one record
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function getRecordAction(Request $request, $databox_id, $record_id)
    {
        try {
            $record = $this->findDataboxById($databox_id)->get_record($record_id);

            return Result::create($request, ['record' => $this->listRecord($request, $record)])->createResponse();
        } catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, $this->app->trans('Record Not Found'))->createResponse();
        } catch (\Exception $e) {
            return $this->getBadRequestAction($request, $this->app->trans('An error occurred'));
        }
    }

    /**
     * Move a record to another collection
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function setRecordCollectionAction(Request $request, $databox_id, $record_id)
    {
        $databox = $this->findDataboxById($databox_id);
        $record = $databox->get_record($record_id);

        try {
            $collection = \collection::getByBaseId($this->app, $request->get('base_id'));
            $record->move_to_collection($collection, $this->getApplicationBox());

            return Result::create($request, ["record" => $this->listRecord($request, $record)])->createResponse();
        } catch (\Exception $e) {
            return $this->getBadRequestAction($request, $e->getMessage());
        }
    }

    /**
     * Return the baskets list of the authenticated user
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function searchBasketsAction(Request $request)
    {
        return Result::create($request, ['baskets' => $this->listBaskets()])->createResponse();
    }

    /**
     * Return a baskets list
     **
     * @return array
     */
    private function listBaskets()
    {
        /** @var BasketRepository $repo */
        $repo = $this->app['repo.baskets'];

        return array_map(function (Basket $basket) {
            return $this->listBasket($basket);
        }, $repo->findActiveByUser($this->getAuthenticatedUser()));
    }

    /**
     * Create a new basket
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function createBasketAction(Request $request)
    {
        $name = $request->get('name');

        if (trim(strip_tags($name)) === '') {
            return $this->getBadRequestAction($request, 'Missing basket name parameter');
        }

        $Basket = new Basket();
        $Basket->setUser($this->getAuthenticatedUser());
        $Basket->setName($name);

        /** @var EntityManager $em */
        $em = $this->app['orm.em'];
        $em->persist($Basket);
        $em->flush();

        return Result::create($request, ["basket" => $this->listBasket($Basket)])->createResponse();
    }

    /**
     * Retrieve a basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return Response
     */
    public function getBasketAction(Request $request, Basket $basket)
    {
        $ret = [
            "basket"          => $this->listBasket($basket),
            "basket_elements" => $this->listBasketContent($request, $basket),
        ];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Retrieve elements of one basket
     *
     * @param Request $request
     * @param Basket $basket
     * @return array
     */
    private function listBasketContent(Request $request, Basket $basket)
    {
        return array_map(function (BasketElement $element) use ($request) {
            return $this->listBasketElement($request, $element);
        }, iterator_to_array($basket->getElements()));
    }

    /**
     * Retrieve detailed information about a basket element
     *
     * @param Request        $request
     * @param BasketElement $basket_element
     * @return array
     */
    private function listBasketElement(Request $request, BasketElement $basket_element)
    {
        $ret = [
            'basket_element_id' => $basket_element->getId(),
            'order'             => $basket_element->getOrd(),
            'record'            => $this->listRecord($request, $basket_element->getRecord($this->app)),
            'validation_item'   => null != $basket_element->getBasket()->getValidation(),
        ];

        if ($basket_element->getBasket()->getValidation()) {
            $choices = [];
            $agreement = null;
            $note = '';

            /** @var ValidationData $validationData */
            foreach ($basket_element->getValidationDatas() as $validationData) {
                $participant = $validationData->getParticipant();
                $user = $participant->getUser();
                $choices[] = [
                    'validation_user' => [
                        'usr_id'         => $user->getId(),
                        'usr_name'       => $user->getDisplayName(),
                        'confirmed'      => $participant->getIsConfirmed(),
                        'can_agree'      => $participant->getCanAgree(),
                        'can_see_others' => $participant->getCanSeeOthers(),
                        'readonly'       => $user->getId() != $this->getAuthenticatedUser()->getId(),
                        'user'           => $this->listUser($user),
                    ],
                    'agreement'       => $validationData->getAgreement(),
                    'updated_on'      => $validationData->getUpdated()->format(DATE_ATOM),
                    'note'            => null === $validationData->getNote() ? '' : $validationData->getNote(),
                ];

                if ($user->getId() == $this->getAuthenticatedUser()->getId()) {
                    $agreement = $validationData->getAgreement();
                    $note = null === $validationData->getNote() ? '' : $validationData->getNote();
                }

                $ret['validation_choices'] = $choices;
            }

            $ret['agreement'] = $agreement;
            $ret['note'] = $note;
        }

        return $ret;
    }

    /**
     * Change the name of one basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return Response
     */
    public function setBasketTitleAction(Request $request, Basket $basket)
    {
        $basket->setName($request->get('name'));

        /** @var EntityManager $em */
        $em = $this->app['orm.em'];
        $em->persist($basket);
        $em->flush();

        return Result::create($request, ["basket" => $this->listBasket($basket)])->createResponse();
    }

    /**
     * Change the description of one basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return Response
     */
    public function setBasketDescriptionAction(Request $request, Basket $basket)
    {
        $basket->setDescription($request->get('description'));

        /** @var EntityManager $em */
        $em = $this->app['orm.em'];
        $em->persist($basket);
        $em->flush();

        return Result::create($request, ["basket" => $this->listBasket($basket)])->createResponse();
    }

    /**
     * Delete a basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return array
     */
    public function deleteBasketAction(Request $request, Basket $basket)
    {
        /** @var EntityManager $em */
        $em = $this->app['orm.em'];
        $em->remove($basket);
        $em->flush();

        return $this->searchBasketsAction($request);
    }

    /**
     * List all available feeds
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function searchPublicationsAction(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        /** @var FeedRepository $feedsRepository */
        $feedsRepository = $this->app['repo.feeds'];
        $coll = $feedsRepository->getAllForUser($this->getAclForUser($user));

        $data = array_map(function ($feed) use ($user) {
            return $this->listPublication($feed, $user);
        }, $coll);

        return Result::create($request, ["feeds" => $data])->createResponse();
    }

    /**
     * Retrieve detailed information about one feed
     *
     * @param  Feed $feed
     * @param  User $user
     *
     * @return array
     */
    private function listPublication(Feed $feed, User $user = null)
    {
        return [
            'id'            => $feed->getId(),
            'title'         => $feed->getTitle(),
            'subtitle'      => $feed->getSubtitle(),
            'total_entries' => $feed->getCountTotalEntries(),
            'icon'          => $feed->getIconUrl(),
            'public'        => $feed->isPublic(),
            'readonly'      => !$feed->isPublisher($user),
            'deletable'     => $feed->isOwner($user),
            'created_on'    => $feed->getCreatedOn()->format(DATE_ATOM),
            'updated_on'    => $feed->getUpdatedOn()->format(DATE_ATOM),
        ];
    }

    public function getPublicationsAction(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        $restrictions = (array) ($request->get('feeds') ?: []);

        $feed = Aggregate::createFromUser($this->app, $user, $restrictions);

        $offset_start = (int) ($request->get('offset_start') ?: 0);
        $per_page = (int) ($request->get('per_page') ?: 5);

        $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 20;

        $data = [
            'total_entries' => $feed->getCountTotalEntries(),
            'offset_start'  => $offset_start,
            'per_page'      => $per_page,
            'entries'       => $this->listPublicationsEntries($request, $feed, $offset_start, $per_page),
        ];

        return Result::create($request, $data)->createResponse();
    }

    /**
     * Retrieve all entries of one feed
     *
     * @param Request        $request
     * @param FeedInterface $feed
     * @param int           $offset_start
     * @param int           $how_many
     * @return array
     */
    private function listPublicationsEntries(Request $request, FeedInterface $feed, $offset_start = 0, $how_many = 5)
    {
        return array_map(function ($entry) use ($request) {
            return $this->listPublicationEntry($request, $entry);
        }, $feed->getEntries()->slice($offset_start, $how_many));
    }

    /**
     * Retrieve detailed information about one feed entry
     *
     * @param Request    $request
     * @param FeedEntry $entry
     * @return array
     */
    private function listPublicationEntry(Request $request, FeedEntry $entry)
    {
        $items = array_map(function ($item) use ($request) {
            return $this->listPublicationEntryItem($request, $item);
        }, iterator_to_array($entry->getItems()));

        return [
            'id'           => $entry->getId(),
            'author_email' => $entry->getAuthorEmail(),
            'author_name'  => $entry->getAuthorName(),
            'created_on'   => $entry->getCreatedOn()->format(DATE_ATOM),
            'updated_on'   => $entry->getUpdatedOn()->format(DATE_ATOM),
            'title'        => $entry->getTitle(),
            'subtitle'     => $entry->getSubtitle(),
            'items'        => $items,
            'feed_id'      => $entry->getFeed()->getId(),
            'feed_title'   => $entry->getFeed()->getTitle(),
            'feed_url'     => '/feeds/' . $entry->getFeed()->getId() . '/content/',
            'url'          => '/feeds/entry/' . $entry->getId() . '/',
        ];
    }

    /**
     * Retrieve detailed information about one feed  entry item
     *
     * @param Request   $request
     * @param FeedItem $item
     * @return array
     */
    private function listPublicationEntryItem(Request $request, FeedItem $item)
    {
        return [
            'item_id' => $item->getId(),
            'record'  => $this->listRecord($request, $item->getRecord($this->app)),
        ];
    }

    public function getFeedEntryAction(Request $request, $entry_id)
    {
        $user = $this->getAuthenticatedUser();
        /** @var FeedEntryRepository $repository */
        $repository = $this->app['repo.feed-entries'];
        /** @var FeedEntry $entry */
        $entry = $repository->find($entry_id);
        $collection = $entry->getFeed()->getCollection($this->app);

        if (null !== $collection && !$this->getAclForUser($user)->has_access_to_base($collection->get_base_id())) {
            return Result::createError($request, 403, 'You have not access to the parent feed')->createResponse();
        }

        return Result::create($request, ['entry' => $this->listPublicationEntry($request, $entry)])->createResponse();
    }

    /**
     * Retrieve one feed
     *
     * @param  Request $request
     * @param  int     $feed_id
     *
     * @return Response
     */
    public function getPublicationAction(Request $request, $feed_id)
    {
        $user = $this->getAuthenticatedUser();
        /** @var FeedRepository $repository */
        $repository = $this->app['repo.feeds'];
        /** @var Feed $feed */
        $feed = $repository->find($feed_id);

        if (!$feed->isAccessible($user, $this->app)) {
            return Result::create($request, [])->createResponse();
        }

        $offset_start = (int) $request->get('offset_start', 0);
        $per_page = (int) $request->get('per_page', 5);

        $per_page = (($per_page >= 1) && ($per_page <= 100)) ? $per_page : 100;

        $data = [
            'feed'         => $this->listPublication($feed, $user),
            'offset_start' => $offset_start,
            'per_page'     => $per_page,
            'entries'      => $this->listPublicationsEntries($request, $feed, $offset_start, $per_page),
        ];

        return Result::create($request, $data)->createResponse();
    }

    /**
     * Get a Response containing the story embed files
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return Response
     */
    public function getStoryEmbedAction(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        $devices = $request->get('devices', []);
        $mimes = $request->get('mimes', []);

        $ret = array_values(array_filter(array_map(function ($media) use ($request, $record) {
            return $this->listEmbeddableMedia($request, $record, $media);
        }, $record->get_embedable_medias($devices, $mimes))));

        return Result::create($request, ["embed" => $ret])->createResponse();
    }

    /**
     * Return detailed information about one story
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function getStoryAction(Request $request, $databox_id, $record_id)
    {
        try {
            $story = $this->findDataboxById($databox_id)->get_record($record_id);

            return Result::create($request, ['story' => $this->listStory($request, $story)])->createResponse();
        } catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, $this->app->trans('Story Not Found'))->createResponse();
        } catch (\Exception $e) {
            return $this->getBadRequestAction($request, $this->app->trans('An error occurred'));
        }
    }

    public function createStoriesAction(Request $request)
    {
        $data = $this->decodeJsonBody($request, 'stories.json');

        $storyData = $data->{'stories'};

        $stories = array();
        foreach ($storyData as $data) {
            $stories[] = $this->createStory($data);
        }

        $result = Result::create($request, array('stories' => array_map(function(\record_adapter $story) {
            return sprintf('/stories/%s/%s/', $story->getDataboxId(), $story->getRecordId());
        }, $stories)));

        return $result->createResponse();
    }

    /**
     * @param object $data
     * @return \record_adapter
     * @throws \Exception
     */
    protected function createStory($data)
    {
        $collection = \collection::getByBaseId($this->app, $data->{'base_id'});

        if (!$this->getAclForUser()->has_right_on_base($collection->get_base_id(), 'canaddrecord')) {
            $this->app->abort(403, sprintf('You can not create a story on this collection %s', $collection->get_base_id()));
        }

        $story = \record_adapter::createStory($this->app, $collection);

        if (isset($data->{'title'})) {
            $story->set_original_name((string) $data->{'title'});
        }

        // set metadata for the story
        $metadatas = array();
        $thumbtitle_set = false;
        /** @var \databox_field $field */
        foreach ($collection->get_databox()->get_meta_structure() as $field) {
            // the title goes into the first 'thumbtitle' field
            if (isset($data->{'title'}) && !$thumbtitle_set && $field->get_thumbtitle()) {
                $metadatas[] = array(
                    'meta_struct_id' => $field->get_id(),
                    'meta_id' => null,
                    'value' => $data->{'title'},
                );
                $thumbtitle_set = true;
            }
            // if the field is set into data->metadatas, set it
            $meta = null;
            // the meta form json can be keyed by id or name
            if(isset($data->{'metadatas'}->{$field->get_id()})) {
                $meta = $data->{'metadatas'}->{$field->get_id()};
            }
            elseif(isset($data->{'metadatas'}->{$field->get_name()})) {
                $meta = $data->{'metadatas'}->{$field->get_name()};
            }
            if($meta !== null) {
                if(!is_array($meta)) {
                    $meta = array($meta);
                }
                foreach($meta as $value) {
                    $metadatas[] = array(
                        'meta_struct_id' => $field->get_id(),
                        'meta_id' => null,
                        'value' => $value,
                    );
                }
            }
        }

        if(count($metadatas) > 0) {
            $story->set_metadatas($metadatas);
        }

        if (isset($data->{'story_records'})) {
            $recordsData = (array) $data->{'story_records'};
            $this->addOrDelStoryRecordsFromData($story, $recordsData, 'ADD');
        }

        return $story;
    }

    private function addOrDelStoryRecordsFromRequest(Request $request, $databox_id, $story_id, $action)
    {
        $data = $this->decodeJsonBody($request, 'story_records.json');
        $story = new \record_adapter($this->app, $databox_id, $story_id);

        $records = $this->addOrDelStoryRecordsFromData($story, $data->story_records, $action);
        $result = Result::create($request, array('records' => $records));

        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($story));

        return $result->createResponse();
    }

    private function addOrDelStoryRecordsFromData(\record_adapter $story, array $recordsData, $action)
    {
        $records = array();
        $cover_set = false;

        foreach ($recordsData as $data) {
            $records[] = $this->addOrDelStoryRecord($story, $data, $action);
            if($action === 'ADD' && !$cover_set && isset($data->{'use_as_cover'}) && $data->{'use_as_cover'} === true) {
                // because we can try many records as cover source, we let it fail
                $cover_set = ($this->setStoryCover($story, $data->{'record_id'}, true) !== false);
            }
        }

        return $records;
    }

    private function addOrDelStoryRecord(\record_adapter $story, $data, $action)
    {
        $databox_id = $data->{'databox_id'};
        $record_id = $data->{'record_id'};

        if($story->getDataboxId() !== $databox_id) {
            $this->app->abort(409, sprintf(
                'The databox_id %s (for record_id %s) must match the databox_id %s of the story',
                $databox_id,
                $record_id,
                $story->getDataboxId()
            ));
        }

        try {
            $record = new \record_adapter($this->app, $databox_id, $record_id);
        } catch (\Exception_Record_AdapterNotFound $e) {
            $record = null;
            $this->app->abort(404, sprintf('Record identified by databox_is %s and record_id %s could not be found', $databox_id, $record_id));
        }

        if ($action === 'ADD' && !$story->hasChild($record)) {
            $story->appendChild($record);
        }
        elseif ($action === 'DEL' && $story->hasChild($record)) {
            $story->removeChild($record);
        }

        return $record->getId();
    }

    public function addRecordsToStoryAction(Request $request, $databox_id, $story_id)
    {
        return $this->addOrDelStoryRecordsFromRequest($request, $databox_id, $story_id, 'ADD');
    }

    public function delRecordsFromStoryAction(Request $request, $databox_id, $story_id)
    {
        return $this->addOrDelStoryRecordsFromRequest($request, $databox_id, $story_id, 'DEL');
    }

    public function setStoryCoverAction(Request $request, $databox_id, $story_id)
    {
        $data = $this->decodeJsonBody($request, 'story_cover.json');

        $story = new \record_adapter($this->app, $databox_id, $story_id);

        // we do NOT let "setStoryCover()" fail : pass false as last arg
        $record_key = $this->setStoryCover($story, $data->{'record_id'}, false);

        return Result::create($request, array($record_key))->createResponse();
    }

    protected function setStoryCover(\record_adapter $story, $record_id, $can_fail=false)
    {
        try {
            $record = new \record_adapter($this->app, $story->getDataboxId(), $record_id);
        } catch (\Exception_Record_AdapterNotFound $e) {
            $record = null;
            $this->app->abort(404, sprintf('Record identified by databox_id %s and record_id %s could not be found', $story->getDataboxId(), $record_id));
        }

        if (!$story->hasChild($record)) {
            $this->app->abort(404, sprintf('Record identified by databox_id %s and record_id %s is not in the story', $story->getDataboxId(), $record_id));
        }

        if ($record->getType() !== 'image' && $record->getType() !== 'video') {
            // this can fail so we can loop on many records during story creation...
            if($can_fail) {
                return false;
            }
            $this->app->abort(403, sprintf('Record identified by databox_id %s and record_id %s is not an image nor a video', $story->getDataboxId(), $record_id));
        }

        foreach ($record->get_subdefs() as $name => $value) {
            if (!in_array($name, array('thumbnail', 'preview'))) {
                continue;
            }
            $media = $this->app->getMediaFromUri($value->getRealPath());
            $this->getSubdefSubstituer()->substitute($story, $name, $media);
            $this->getDataboxLogger($story->getDatabox())->log(
                $story,
                \Session_Logger::EVENT_SUBSTITUTE,
                $name == 'document' ? 'HD' : $name,
                ''
            );
        }

        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($story));

        return $record->getId();
    }

    public function getCurrentUserAction(Request $request)
    {
        $ret = [
            "user" => $this->listUser($this->getAuthenticatedUser()),
            "collections" => $this->listUserCollections($this->getAuthenticatedUser()),
            "databoxes" => $this->listUserDataboxes($this->getAuthenticatedUser())
        ];

        if (defined('API_SKIP_USER_REGISTRATIONS') && ! constant('API_SKIP_USER_REGISTRATIONS')) {
            // I am infinitely sorry... if you feel like it, you can fix the tests database bootstrapping
            // to use SQLite in all cases and remove this check. Good luck...
            $ret["demands"] = $this->listUserDemands($this->getAuthenticatedUser());
        }

        return Result::create($request, $ret)->createResponse();
    }

    public function deleteCurrentUserAction(Request $request)
    {
        try {
            $service = $this->getAccountService();
            $service->deleteAccount();

            $ret = [ 'success' => true ];
        }
        catch (\Exception $ex) {
            $ret = [ 'success' => false ];
        }

        return Result::create($request, $ret)->createResponse();
    }

    public function updateCurrentUserAction(Request $request)
    {
        $service = $this->getAccountService();
        $data = json_decode($request->getContent(false), true);

        $command = new UpdateAccountCommand();
        $command
            ->setEmail(isset($data['email']) ? $data['email'] : null)
            ->setGender(isset($data['gender']) ? $data['gender'] : null)
            ->setFirstName(isset($data['firstname']) ? $data['firstname'] : null)
            ->setLastName(isset($data['lastname']) ? $data['lastname'] : null)
            ->setZipCode(isset($data['zip_code']) ? $data['zip_code'] : null)
            ->setCity(isset($data['city']) ? $data['city'] : null)
            ->setPhone(isset($data['tel']) ? $data['tel'] : null)
            ->setCompany(isset($data['company']) ? $data['company'] : null)
            ->setJob(isset($data['job']) ? $data['job'] : null)
            ->setNotifications(isset($data['notifications']) ? $data['notifications'] : null);

        try {
            $service->updateAccount($command);
            $ret = [ 'success' => true ];
        }
        catch (AccountException $exception) {
            /** @Ignore */
            $ret = [ 'success' => false, 'message' => $this->app->trans($exception->getMessage()) ];
        }

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @Ignore
     */
    public function updateCurrentUserPasswordAction(Request $request)
    {
        $service = $this->getAccountService();
        $data = json_decode($request->getContent(false), true);
        $command = new UpdatePasswordCommand();
        /** @var Form $form */
        $form = $this->app->form(new PhraseaRenewPasswordForm(), $command, [
            'csrf_protection' => false,
        ]);

        $form->submit($data);

        if ($form->isValid()) {
            try {
                $service->updatePassword($command, null);
                $ret = ['success' => true];
            } catch (AccountException $exception) {
                /** @Ignore */
                $ret = [ 'success' => false, 'message' => $this->app->trans($exception->getMessage()) ];
            }
        } else {
            $ret = [ 'success' => false, 'message' => (string) $form->getErrorsAsString() ];
        }

        return Result::create($request, $ret)->createResponse();
    }

    public function createAccessDemand(Request $request)
    {
        $service = $this->getRegistrationService();
        $data = json_decode($request->getContent(false), true);
        $collections = null;

        if (isset($data['collections'])) {
            $collections = $data['collections'];
        }

        try {
            $user = $service->registerUser($data, $collections);
            $token = $service->getAccountUnlockToken($user);
        }
        catch (RegistrationException $exception) {
            return Result::createError($request, 500, $exception->getMessage())->createResponse();
        }

        return Result::create($request, [
            'user' => $user,
            'token' => $token,
        ])->createResponse();
    }

    public function createCollectionRequests(Request $request)
    {
        $service = $this->getRegistrationService();
        $user = $this->getAuthenticatedUser();
        $data = json_decode($request->getContent(false), true);

        $service->createCollectionRequests($user, $data);

        return Result::create($request, $this->listUserDemands($user))->createResponse();
    }

    public function ensureAdmin(Request $request)
    {
        if (! $this->getApiAuthenticatedUser()->isAdmin()) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureUserManagementRights(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $acl = $this->getAclForUser($user);

        if (! $acl->has_access_to_module('admin') || ! $acl->has_right(\ACL::CANADMIN)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureAccessToDatabox(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $databox = $this->findDataboxById($request->attributes->get('databox_id'));

        if (!$this->getAclForUser($user)->has_access_to_sbas($databox->get_sbas_id())) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureAccessToBase(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $base_id = $request->attributes->get('base_id');

        if (!$this->getAclForUser($user)->has_access_to_base($base_id)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureCanAccessToRecord(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $record = $this->findDataboxById($request->attributes->get('databox_id'))
            ->get_record($request->attributes->get('record_id'));
        if (!$this->getAclForUser($user)->has_access_to_record($record)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureCanModifyRecord(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        if (!$this->getAclForUser($user)->has_right(\ACL::CANMODIFRECORD)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureCanModifyRecordStatus(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $record = $this->findDataboxById($request->attributes->get('databox_id'))
            ->get_record($request->attributes->get('record_id'));
        if (!$this->getAclForUser($user)->has_right_on_base($record->getBaseId(), \ACL::CHGSTATUS)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureCanSeeDataboxStructure(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $databox = $this->findDataboxById($request->attributes->get('databox_id'));
        if (!$this->getAclForUser($user)->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct')) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureCanMoveRecord(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $record = $this->findDataboxById($request->attributes->get('databox_id'))
            ->get_record($request->attributes->get('record_id'));
        // TODO: Check comparison. seems to be a mismatch
        if ((!$this->getAclForUser($user)->has_right(\ACL::CANADDRECORD)
                && !$this->getAclForUser($user)->has_right(\ACL::CANDELETERECORD))
            || !$this->getAclForUser($user)->has_right_on_base($record->getBaseId(), \ACL::CANDELETERECORD)
        ) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureCanDeleteRecord(Request $request)
    {
        $user = $this->getApiAuthenticatedUser();
        $record = $this->findDataboxById($request->attributes->get('databox_id'))
            ->get_record($request->attributes->get('record_id'));

        if (!$this->getAclForUser($user)->has_right_on_base($record->getBaseId(), \ACL::CANDELETERECORD)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }

        return null;
    }

    public function ensureJsonContentType(Request $request)
    {
        if ($request->getContentType() != 'json') {
            $this->app->abort(406, 'Invalid Content Type given.');
        }
    }

    /**
     * @return AccountService
     */
    public function getAccountService()
    {
        return $this->app['accounts.service'];
    }

    /**
     * @return RegistrationService
     */
    public function getRegistrationService()
    {
        return $this->app['authentication.registration_service'];
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return User
     */
    private function getApiAuthenticatedUser()
    {
        /** @var ApiOauthToken $token */
        $token = $this->getSession()->get('token');

        return $token
            ->getAccount()
            ->getUser();
    }

    /**
     * @return TaskRepository
     */
    private function getTaskRepository()
    {
        return $this->app['repo.tasks'];
    }

    /**
     * @return TaskManipulator
     */
    private function getTaskManipulator()
    {
        return $this->app['manipulator.task'];
    }

    /**
     * @return Manager
     */
    private function getBorderManager()
    {
        return $this->app['border-manager'];
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
     * @return \Alchemy\Phrasea\Media\SubdefSubstituer
     */
    private function getSubdefSubstituer()
    {
        return $this->app['subdef.substituer'];
    }

    /**
     * @param Request $request
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordEmbeddableMedias(Request $request, \record_adapter $record)
    {
        $subdefs = [];

        foreach ($record->get_embedable_medias([], []) as $name => $media) {
            if (null !== $subdef = $this->listEmbeddableMedia($request, $record, $media)) {
                $subdefs[] = $subdef;
            }
        }

        return $subdefs;
    }

    /**
     * @param \record_adapter $record
     * @return array
     */
    private function listRecordCaption(\record_adapter $record)
    {
        $includeBusiness = $this->getAclForUser()->can_see_business_fields($record->getDatabox());

        $caption = [];

        foreach ($record->get_caption()->get_fields(null, $includeBusiness) as $field) {
            $caption[] = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'value' => $field->get_serialized_values(';'),
            ];
        }

        return $caption;
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
     * @param RecordCollection|\record_adapter[] $references
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
     * @param \caption_record[] $captions
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
