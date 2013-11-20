<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Manager as BorderManager;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Entities\UserQuery;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class API_V1_adapter extends API_V1_Abstract
{
    /**
     * API Version
     *
     * @var string
     */
    protected $version = '1.3';

    /**
     * Application context
     *
     * @var Application
     */
    protected $app;

    const OBJECT_TYPE_STORY = 'http://api.phraseanet.com/api/objects/story';
    const OBJECT_TYPE_STORY_METADATA_BAG = 'http://api.phraseanet.com/api/objects/story-metadata-bag';

    /**
     * API constructor
     *
     * @param  Application    $app The application context
     * @return API_V1_adapter
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Retrieve  http status error code according to the message
     *
     * @param Request $request
     * @param string  $error
     * @param string  $message
     *
     * @return API_V1_result `
     */
    public function get_error_message(Request $request, $error, $message)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $result->set_error_message($error, $message);

        return $result;
    }

    /**
     * Retrieve  http status error message according to the http status error code
     * @param  Request       $request
     * @param  int           $code
     * @return API_V1_result
     */
    public function get_error_code(Request $request, $code)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $result->set_error_code($code);

        return $result;
    }

    /**
     * Get the current version
     *
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Return an array of key-values informations about scheduler
     *
     * @param  Application    $app The silex application
     * @return \API_V1_result
     */
    public function get_scheduler(Application $app)
    {
        $result = new \API_V1_result($app, $app['request'], $this);
        $date = new \DateTime();
        $data = $app['task-manager.live-information']->getManager();

        $result->set_datas(['scheduler' => [
            'configuration' => $data['configuration'],
            'state'         => $data['actual'],
            'status'        => $data['actual'],
            'pid'           => $data['process-id'],
            'process-id'    => $data['process-id'],
            'updated_on'    => $date->format(DATE_ATOM),
        ]]);

        return $result;
    }

    /**
     * Get a list of phraseanet tasks
     *
     * @param Application $app The API silex application
     *
     * @return \API_V1_result
     */
    public function get_task_list(Application $app)
    {
        $result = new \API_V1_result($app, $app['request'], $this);

        $ret = [];
        foreach ($app['manipulator.task']->getRepository()->findAll() as $task) {
            $ret[] = $this->list_task($app, $task);
        }

        $result->set_datas(['tasks' => $ret]);

        return $result;
    }

    protected function list_task(Application $app, Task $task)
    {
        $data = $app['task-manager.live-information']->getTask($task);

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
            'last_exec_time' => $task->getLastExecution() ? $task->getLastExecution()->format(DATE_ATOM) : null,
            'last_execution' => $task->getLastExecution() ? $task->getLastExecution()->format(DATE_ATOM) : null,
            'updated'        => $task->getUpdated() ? $task->getUpdated()->format(DATE_ATOM) : null,
            'created'        => $task->getCreated() ? $task->getCreated()->format(DATE_ATOM) : null,
            'auto_start'     => $task->getStatus() === Task::STATUS_STARTED,
            'crashed'        => $task->getCrashed(),
            'status'         => $task->getStatus(),
        ];
    }

    /**
     * Get informations about an identified task
     *
     * @param  \Silex\Application $app  The API silex application
     * @param  Task               $task
     * @return \API_V1_result
     */
    public function get_task(Application $app, Task $task)
    {
        $result = new \API_V1_result($app, $app['request'], $this);
        $ret = ['task' => $this->list_task($app, $task)];
        $result->set_datas($ret);

        return $result;
    }

    /**
     * Start a specified task
     *
     * @param  \Silex\Application $app  The API silex application
     * @param  Task               $task The task to start
     * @return \API_V1_result
     */
    public function start_task(Application $app, Task $task)
    {
        $result = new \API_V1_result($app, $app['request'], $this);

        $app['manipulator.task']->start($task);
        $result->set_datas(['task' => $this->list_task($app, $task)]);

        return $result;
    }

    /**
     * Stop a specified task
     *
     * @param  \Silex\Application $app  The API silex application
     * @param  Task               $task The task to stop
     * @return \API_V1_result
     */
    public function stop_task(Application $app, Task $task)
    {
        $result = new API_V1_result($app, $app['request'], $this);

        $app['manipulator.task']->stop($task);
        $result->set_datas(['task' => $this->list_task($app, $task)]);

        return $result;
    }

    /**
     * Update a task property
     *  - name
     *  - autostart
     *
     * @param  \Silex\Application           $app  Silex application
     * @param  Task                         $task The task
     * @return \API_V1_result
     * @throws \API_V1_exception_badrequest
     */
    public function set_task_property(Application $app, $task)
    {
        $result = new API_V1_result($app, $app['request'], $this);

        $title = $app['request']->get('title');
        $autostart = $app['request']->get('autostart');

        if (null === $title && null === $autostart) {
            throw new \API_V1_exception_badrequest();
        }

        if ($title) {
            $task->setName($title);
        }
        if ($autostart) {
            $task->setStatus(Task::STATUS_STARTED);
        }

        $result->set_datas(['task' => $this->list_task($app, $task)]);

        return $result;
    }

    /**
     * Get Information the cache system used by the instance
     *
     * @param  \Silex\Application $app the silex application
     * @return array
     */
    protected function get_cache_info(Application $app)
    {
        $caches = [
            'main'               => $app['cache'],
            'op_code'            => $app['opcode-cache'],
            'doctrine_metadatas' => $this->app['EM']->getConfiguration()->getMetadataCacheImpl(),
            'doctrine_query'     => $this->app['EM']->getConfiguration()->getQueryCacheImpl(),
            'doctrine_result'    => $this->app['EM']->getConfiguration()->getResultCacheImpl(),
        ];

        $ret = [];

        foreach ($caches as $name => $service) {
            if ($service instanceof \Alchemy\Phrasea\Cache\Cache) {
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

    /**
     * Provide information about phraseanet configuration
     *
     * @param  \Silex\Application $app the silex application
     * @return array
     */
    protected function get_config_info(Application $app)
    {
        $ret = [];

        $ret['phraseanet']['version'] = [
            'name'   => $app['phraseanet.version']::getName(),
            'number' => $app['phraseanet.version']::getNumber(),
        ];

        $ret['phraseanet']['environment'] = $app->getEnvironment();
        $ret['phraseanet']['debug'] = $app['debug'];
        $ret['phraseanet']['maintenance'] = $app['conf']->get(['main', 'maintenance']);
        $ret['phraseanet']['errorsLog'] = $app['debug'];
        $ret['phraseanet']['serverName'] = $app['conf']->get(['main', 'servername']);

        return $ret;
    }

    /**
     * Provide phraseanet global values
     * @param  \Silex\Application $app the silex application
     * @return array
     */
    protected function get_gv_info(Application $app)
    {
        try {
            $SEStatus = $app['phraseanet.SE']->getStatus();
        } catch (\RuntimeException $e) {
            $SEStatus = ['error' => $e->getMessage()];
        }

        $binaries = $app['conf']->get('binaries');

        return [
            'global_values' => [
                'serverName'  => $app['phraseanet.registry']->get('GV_ServerName'),
                'title'       => $app['phraseanet.registry']->get('GV_homeTitle'),
                'keywords'    => $app['phraseanet.registry']->get('GV_metaKeywords'),
                'description' => $app['phraseanet.registry']->get('GV_metaDescription'),
                'httpServer'  => [
                    'logErrors'       => $app['phraseanet.registry']->get('GV_log_errors'),
                    'phpTimezone'     => ini_get('date.timezone'),
                    'siteId'          => $app['conf']->get(['main', 'key']),
                    'staticUrl'       => $app['phraseanet.registry']->get('GV_STATIC_URL'),
                    'defaultLanguage' => $app['phraseanet.registry']->get('id_GV_default_lng'),
                    'allowIndexing'   => $app['phraseanet.registry']->get('GV_allow_search_engine'),
                    'modes'           => [
                        'XsendFile'                     => $app['conf']->get(['xsendfile', 'enabled']),
                        'XsendFileMapping'              => $app['conf']->get(['xsendfile', 'mapping']),
                        'h264Streaming'                 => $app['phraseanet.registry']->get('GV_h264_streaming'),
                        'authTokenDirectory'            => $app['phraseanet.registry']->get('GV_mod_auth_token_directory'),
                        'authTokenDirectoryPath'        => $app['phraseanet.registry']->get('GV_mod_auth_token_directory_path'),
                        'authTokenPassphrase'           => $app['phraseanet.registry']->get('GV_mod_auth_token_passphrase'),
                    ]
                ],
                'maintenance' => [
                    'alertMessage'   => $app['phraseanet.registry']->get('GV_message'),
                    'displayMessage' => $app['phraseanet.registry']->get('GV_message_on'),
                ],
                'webServices'    => [
                    'googleApi'                   => $app['phraseanet.registry']->get('GV_google_api'),
                    'googleAnalyticsId'           => $app['phraseanet.registry']->get('GV_googleAnalytics'),
                    'i18nWebService'              => $app['phraseanet.registry']->get('GV_i18n_service'),
                    'recaptacha'                  => [
                        'active'     => $app['phraseanet.registry']->get('GV_captchas'),
                        'publicKey'  => $app['phraseanet.registry']->get('GV_captcha_public_key'),
                        'privateKey' => $app['phraseanet.registry']->get('GV_captcha_private_key'),
                    ],
                    'youtube'    => [
                        'active'       => $app['phraseanet.registry']->get('GV_youtube_api'),
                        'clientId'     => $app['phraseanet.registry']->get('GV_youtube_client_id'),
                        'clientSecret' => $app['phraseanet.registry']->get('GV_youtube_client_secret'),
                        'devKey'       => $app['phraseanet.registry']->get('GV_youtube_dev_key'),
                    ],
                    'flickr'       => [
                        'active'       => $app['phraseanet.registry']->get('GV_flickr_api'),
                        'clientId'     => $app['phraseanet.registry']->get('GV_flickr_client_id'),
                        'clientSecret' => $app['phraseanet.registry']->get('GV_flickr_client_secret'),
                    ],
                    'dailymtotion' => [
                        'active'       => $app['phraseanet.registry']->get('GV_dailymotion_api'),
                        'clientId'     => $app['phraseanet.registry']->get('GV_dailymotion_client_id'),
                        'clientSecret' => $app['phraseanet.registry']->get('GV_dailymotion_client_secret'),
                    ]
                ],
                'navigator'    => [
                    'active'   => $app['phraseanet.registry']->get('GV_client_navigator'),
                ],
                'office-plugin' => [
                    'active'    => $app['phraseanet.registry']->get('GV_client_navigator'),
                ],
                'homepage' => [
                    'viewType' => $app['phraseanet.registry']->get('GV_home_publi'),
                ],
                'report'   => [
                    'anonymous' => $app['phraseanet.registry']->get('GV_anonymousReport'),
                ],
                'events'    => [
                    'events'        => $app['phraseanet.registry']->get('GV_events'),
                    'notifications' => $app['phraseanet.registry']->get('GV_notifications'),
                ],
                'upload'        => [
                    'allowedFileExtension' => $app['phraseanet.registry']->get('GV_appletAllowedFileEx'),
                ],
                'filesystem'           => [
                    'noWeb'        => $app['phraseanet.registry']->get('GV_base_datapath_noweb'),
                ],
                'searchEngine' => [
                    'configuration' => [
                        'defaultQuery'     => $app['phraseanet.registry']->get('GV_defaultQuery'),
                        'defaultQueryType' => $app['phraseanet.registry']->get('GV_defaultQuery_type'),
                        'minChar'          => $app['phraseanet.registry']->get('GV_min_letters_truncation'),
                        'sort'             => $app['phraseanet.registry']->get('GV_phrasea_sort'),
                    ],
                    'engine'            => [
                        'type'          => $app['phraseanet.SE']->getName(),
                        'status'        => $SEStatus,
                        'configuration' => $app['phraseanet.SE']->getConfigurationPanel()->getConfiguration(),
                    ],
                ],
                'binary'  => [
                    'phpCli'            => isset($binaries['php_binary']) ? $binaries['php_binary'] : null,
                    'phpIni'            => $app['phraseanet.registry']->get('GV_PHP_INI'),
                    'swfExtract'        => isset($binaries['swf_extract_binary']) ? $binaries['swf_extract_binary'] : null,
                    'pdf2swf'           => isset($binaries['pdf2swf_binary']) ? $binaries['pdf2swf_binary'] : null,
                    'swfRender'         => isset($binaries['swf_render_binary']) ? $binaries['swf_render_binary'] : null,
                    'unoconv'           => isset($binaries['unoconv_binary']) ? $binaries['unoconv_binary'] : null,
                    'ffmpeg'            => isset($binaries['ffmpeg_binary']) ? $binaries['ffmpeg_binary'] : null,
                    'ffprobe'           => isset($binaries['ffprobe_binary']) ? $binaries['ffprobe_binary'] : null,
                    'mp4box'            => isset($binaries['mp4box_binary']) ? $binaries['mp4box_binary'] : null,
                    'pdftotext'         => isset($binaries['pdftotext_binary']) ? $binaries['pdftotext_binary'] : null,
                    'recess'            => isset($binaries['recess_binary']) ? $binaries['recess_binary'] : null,
                    'pdfmaxpages'       => $app['phraseanet.registry']->get('GV_pdfmaxpages'),],
                'mainConfiguration' => [
                    'adminMail'          => $app['phraseanet.registry']->get('GV_adminMail'),
                    'viewBasAndCollName' => $app['phraseanet.registry']->get('GV_view_bas_and_coll'),
                    'chooseExportTitle'  => $app['phraseanet.registry']->get('GV_choose_export_title'),
                    'defaultExportTitle' => $app['phraseanet.registry']->get('GV_default_export_title'),
                    'socialTools'        => $app['phraseanet.registry']->get('GV_social_tools'),],
                'modules'            => [
                    'thesaurus'          => $app['phraseanet.registry']->get('GV_thesaurus'),
                    'storyMode'          => $app['phraseanet.registry']->get('GV_multiAndReport'),
                    'docSubsitution'     => $app['phraseanet.registry']->get('GV_seeOngChgDoc'),
                    'subdefSubstitution' => $app['phraseanet.registry']->get('GV_seeNewThumb'),],
                'email'              => [
                    'defaultMailAddress' => $app['phraseanet.registry']->get('GV_defaulmailsenderaddr'),
                    'smtp'               => [
                        'active'   => $app['phraseanet.registry']->get('GV_smtp'),
                        'auth'     => $app['phraseanet.registry']->get('GV_smtp_auth'),
                        'host'     => $app['phraseanet.registry']->get('GV_smtp_host'),
                        'port'     => $app['phraseanet.registry']->get('GV_smtp_port'),
                        'secure'   => $app['phraseanet.registry']->get('GV_smtp_secure'),
                        'user'     => $app['phraseanet.registry']->get('GV_smtp_user'),
                        'password' => $app['phraseanet.registry']->get('GV_smtp_password'),
                    ],
                ],
                'ftp'      => [
                    'active'        => $app['phraseanet.registry']->get('GV_activeFTP'),
                    'activeForUser' => $app['phraseanet.registry']->get('GV_ftp_for_user'),],
                'client'        => [
                    'maxSizeDownload'         => $app['phraseanet.registry']->get('GV_download_max'),
                    'tabSearchMode'           => $app['phraseanet.registry']->get('GV_ong_search'),
                    'tabAdvSearchPosition'    => $app['phraseanet.registry']->get('GV_ong_advsearch'),
                    'tabTopicsPosition'       => $app['phraseanet.registry']->get('GV_ong_topics'),
                    'tabOngActifPosition'     => $app['phraseanet.registry']->get('GV_ong_actif'),
                    'renderTopicsMode'        => $app['phraseanet.registry']->get('GV_client_render_topics'),
                    'displayRolloverPreview'  => $app['phraseanet.registry']->get('GV_rollover_reg_preview'),
                    'displayRolloverBasket'   => $app['phraseanet.registry']->get('GV_rollover_chu'),
                    'collRenderMode'          => $app['phraseanet.registry']->get('GV_client_coll_ckbox'),
                    'viewSizeBaket'           => $app['phraseanet.registry']->get('GV_viewSizeBaket'),
                    'clientAutoShowProposals' => $app['phraseanet.registry']->get('GV_clientAutoShowProposals'),
                    'needAuth2DL'             => $app['phraseanet.registry']->get('GV_needAuth2DL'),],
                'inscription'             => [
                    'autoSelectDB' => $app['phraseanet.registry']->get('GV_autoselectDB'),
                    'autoRegister' => $app['phraseanet.registry']->get('GV_autoregister'),
                ],
                'push'         => [
                    'validationReminder' => $app['phraseanet.registry']->get('GV_validation_reminder'),
                    'expirationValue'    => $app['phraseanet.registry']->get('GV_val_expiration'),
                ],
            ]
        ];
    }

    /**
     * Provide
     *  - cache information
     *  - global values informations
     *  - configuration informations
     *
     * @param  \Silex\Application $app the silex application
     * @return \API_V1_result
     */
    public function get_phraseanet_monitor(Application $app)
    {
        $result = new API_V1_result($app, $app['request'], $this);

        $ret = array_merge(
                $this->get_config_info($app), $this->get_cache_info($app), $this->get_gv_info($app)
        );

        $result->set_datas($ret);

        return $result;
    }

    /**
     * Get an API_V1_result containing the databoxes
     *
     * @param Request $request
     *
     * @return API_V1_result
     */
    public function get_databoxes(Request $request)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas(["databoxes" => $this->list_databoxes()]);

        return $result;
    }

    /**
     * Get an API_V1_result containing the collections of a databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return API_V1_result
     */
    public function get_databox_collections(Request $request, $databox_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas(
                [
                    "collections" => $this->list_databox_collections(
                            $this->app['phraseanet.appbox']->get_databox($databox_id)
                    )
                ]
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the status of a databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return API_V1_result
     */
    public function get_databox_status(Request $request, $databox_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas(
                [
                    "status" =>
                    $this->list_databox_status(
                            $this->app['phraseanet.appbox']->get_databox($databox_id)->get_statusbits()
                    )
                ]
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the metadatas of a databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return API_V1_result
     */
    public function get_databox_metadatas(Request $request, $databox_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas(
                [
                    "document_metadatas" =>
                    $this->list_databox_metadatas_fields(
                            $this->app['phraseanet.appbox']->get_databox($databox_id)
                                    ->get_meta_structure()
                    )
                ]
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the terms of use of a databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return API_V1_result
     */
    public function get_databox_terms(Request $request, $databox_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas(
                [
                    "termsOfUse" =>
                    $this->list_databox_terms($this->app['phraseanet.appbox']->get_databox($databox_id))
                ]
        );

        return $result;
    }

    public function caption_records(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $record = $this->app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);
        $fields = $record->get_caption()->get_fields();

        $ret = ['caption_metadatas' => []];

        foreach ($fields as $field) {
            $ret['caption_metadatas'][] = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name'              => $field->get_name(),
                'value'             => $field->get_serialized_values(";"),
            ];
        }

        $result->set_datas($ret);

        return $result;
    }

    public function add_record(Application $app, Request $request)
    {
        if (count($request->files->get('file')) == 0) {
            throw new API_V1_exception_badrequest('Missing file parameter');
        }

        if (!$request->files->get('file') instanceof Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new API_V1_exception_badrequest('You can upload one file at time');
        }

        $file = $request->files->get('file');
        /* @var $file Symfony\Component\HttpFoundation\File\UploadedFile */

        if (!$file->isValid()) {
            throw new API_V1_exception_badrequest('Datas corrupted, please try again');
        }

        if (!$request->get('base_id')) {
            throw new API_V1_exception_badrequest('Missing base_id parameter');
        }

        $collection = \collection::get_from_base_id($this->app, $request->get('base_id'));

        if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($request->get('base_id'), 'canaddrecord')) {
            throw new API_V1_exception_forbidden(sprintf('You do not have access to collection %s', $collection->get_label($this->app['locale.I18n'])));
        }

        $media = $app['mediavorus']->guess($file->getPathname());

        $Package = new File($this->app, $media, $collection, $file->getClientOriginalName());

        if ($request->get('status')) {
            $Package->addAttribute(new Status($app, $request->get('status')));
        }

        $session = new Alchemy\Phrasea\Model\Entities\LazaretSession();
        $session->setUsrId($app['authentication']->getUser()->get_id());

        $app['EM']->persist($session);
        $app['EM']->flush();

        $reasons = $output = null;

        $callback = function ($element, $visa, $code) use (&$reasons, &$output) {
                    if (!$visa->isValid()) {
                        $reasons = [];

                        foreach ($visa->getResponses() as $response) {
                            $reasons[] = $response->getMessage();
                        }
                    }

                    $output = $element;
                };

        switch ($request->get('forceBehavior')) {
            case '0' :
                $behavior = BorderManager::FORCE_RECORD;
                break;
            case '1' :
                $behavior = BorderManager::FORCE_LAZARET;
                break;
            case null:
                $behavior = null;
                break;
            default:
                throw new API_V1_exception_badrequest(sprintf('Invalid forceBehavior value `%s`', $request->get('forceBehavior')));
                break;
        }

        $app['border-manager']->process($session, $Package, $callback, $behavior);

        $ret = [
            'entity' => null,
        ];

        if ($output instanceof \record_adapter) {
            $ret['entity'] = '0';
            $ret['url'] = '/records/' . $output->get_sbas_id() . '/' . $output->get_record_id() . '/';
            $app['phraseanet.SE']->addRecord($output);
        }
        if ($output instanceof LazaretFile) {
            $ret['entity'] = '1';
            $ret['url'] = '/quarantine/item/' . $output->getId() . '/';
        }

        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas($ret);

        return $result;
    }

    public function list_quarantine(Application $app, Request $request)
    {
        $offset_start = max($request->get('offset_start', 0), 0);
        $per_page = min(max($request->get('per_page', 10), 1), 20);

        $baseIds = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base(['canaddrecord']));

        $lazaretFiles = [];

        if (count($baseIds) > 0) {
            $lazaretRepository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\LazaretFile');

            $lazaretFiles = $lazaretRepository->findPerPage(
                    $baseIds, $offset_start, $per_page
            );
        }

        $ret = [];

        foreach ($lazaretFiles as $lazaretFile) {
            $ret[] = $this->list_lazaret_file($lazaretFile);
        }

        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas([
            'offset_start'     => $offset_start,
            'per_page'         => $per_page,
            'quarantine_items' => $ret,
        ]);

        return $result;
    }

    public function list_quarantine_item($lazaret_id, Application $app, Request $request)
    {
        $lazaretFile = $app['EM']->find('Alchemy\Phrasea\Model\Entities\LazaretFile', $lazaret_id);

        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            throw new \API_V1_exception_notfound(sprintf('Lazaret file id %d not found', $lazaret_id));
        }

        if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($lazaretFile->getBaseId(), 'canaddrecord')) {
            throw new \API_V1_exception_forbidden('You do not have access to this quarantine item');
        }

        $ret = ['quarantine_item' => $this->list_lazaret_file($lazaretFile)];

        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas($ret);

        return $result;
    }

    protected function list_lazaret_file(LazaretFile $file)
    {
        $checks = [];

        if ($file->getChecks()) {
            foreach ($file->getChecks() as $checker) {

                $checks[] = $checker->getMessage();
            }
        }

        $usr_id = null;
        if ($file->getSession()->getUser($this->app)) {
            $usr_id = $file->getSession()->getUser($this->app)->get_id();
        }

        $session = [
            'id'     => $file->getSession()->getId(),
            'usr_id' => $usr_id,
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
            'created_on' => $file->getCreated()->format(DATE_ATOM),
            'updated_on' => $file->getUpdated()->format(DATE_ATOM),
        ];
    }

    /**
     * Search for results
     *
     * @param  Request        $request
     * @return \API_V1_result
     */
    public function search(Request $request)
    {
        $result = new API_V1_result($this->app, $request, $this);

        list($ret, $search_result) = $this->prepare_search_request($request);

        $ret['results'] = ['records' => [], 'stories' => []];

        foreach ($search_result->getResults() as $record) {
            if ($record->is_grouping()) {
                $ret['results']['stories'][] = $this->list_story($record);
            } else {
                $ret['results']['records'][] = $this->list_record($record);
            }
        }

        /**
         * @todo donner des highlights
         */
        $result->set_datas($ret);

        return $result;
    }

    /**
     * Get an API_V1_result containing the results of a records search
     *
     * Deprecated in favor of search
     *
     * @param Request $request
     *
     * @return API_V1_result
     */
    public function search_records(Request $request)
    {
        $result = new API_V1_result($this->app, $request, $this);

        list($ret, $search_result) = $this->prepare_search_request($request);

        foreach ($search_result->getResults() as $record) {
            $ret['results'][] = $this->list_record($record);
        }

        /**
         * @todo donner des highlights
         */
        $result->set_datas($ret);

        return $result;
    }

    private function prepare_search_request(Request $request)
    {
        $options = SearchEngineOptions::fromRequest($this->app, $request);

        $offsetStart = (int) ($request->get('offset_start') ? : 0);
        $perPage = (int) $request->get('per_page') ? : 10;

        $query = (string) $request->get('query');

        $this->app['phraseanet.SE']->setOptions($options);
        $this->app['phraseanet.SE']->resetCache();

        $search_result = $this->app['phraseanet.SE']->query($query, $offsetStart, $perPage);

        $userQuery = new UserQuery();
        $userQuery->setUsrId($this->app['authentication']->getUser()->get_id());
        $userQuery->setQuery($query);

        $this->app['EM']->persist($userQuery);
        $this->app['EM']->flush();

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function (\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $this->app['phraseanet.SE.logger']->log($databox, $search_result->getQuery(), $search_result->getTotal(), $colls);
        }

        $ret = [
            'offset_start'      => $offsetStart,
            'per_page'          => $perPage,
            'available_results' => $search_result->getAvailable(),
            'total_results'     => $search_result->getTotal(),
            'error'             => $search_result->getError(),
            'warning'           => $search_result->getWarning(),
            'query_time'        => $search_result->getDuration(),
            'search_indexes'    => $search_result->getIndexes(),
            'suggestions'       => array_map(function (SearchEngineSuggestion $suggestion) {
                return $suggestion->toArray();
            }, $search_result->getSuggestions()->toArray()),
            'results'           => [],
            'query'             => $search_result->getQuery(),
        ];

        return [$ret, $search_result];
    }

    /**
     * Get an API_V1_result containing the baskets where the record is in
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return API_V1_result
     */
    public function get_record_related(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $that = $this;
        $baskets = array_map(function ($basket) use ($that) {
            return $that->list_basket($basket);
            }, (array) $this->app['phraseanet.appbox']
                ->get_databox($databox_id)
                ->get_record($record_id)
                ->get_container_baskets($this->app['EM'], $this->app['authentication']->getUser())
        );

        $record = $this->app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $stories = array_map(function ($story) use ($that) {
            return $that->list_story($story);
        }, array_values($record->get_grouping_parents()->get_elements()));

        $result->set_datas([
            "baskets" => $baskets,
            "stories" => $stories,
        ]);

        return $result;
    }

    /**
     * Get an API_V1_result containing the record metadatas
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return API_V1_result
     */
    public function get_record_metadatas(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $record = $this->app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $result->set_datas(
                [
                    "record_metadatas" => $this->list_record_caption($record->get_caption())
                ]
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the record status
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return API_V1_result
     */
    public function get_record_status(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $record = $this->app['phraseanet.appbox']
                ->get_databox($databox_id)
                ->get_record($record_id);

        $result->set_datas(
                [
                    "status" =>
                    $this->list_record_status(
                            $this->app['phraseanet.appbox']->get_databox($databox_id)
                            , $record->get_status()
                    )
                ]
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the record embed files
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return API_V1_result
     */
    public function get_record_embed(Request $request, $databox_id, $record_id)
    {

        $result = new API_V1_result($this->app, $request, $this);

        $record = $this->app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $ret = [];

        $devices = $request->get('devices', []);
        $mimes = $request->get('mimes', []);

        foreach ($record->get_embedable_medias($devices, $mimes) as $name => $media) {
            $ret[] = $this->list_embedable_media($media, $this->app['phraseanet.registry']);
        }

        $result->set_datas(["embed" => $ret]);

        return $result;
    }

    /**
     * Get an API_V1_result containing the story embed files
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return API_V1_result
     */
    public function get_story_embed(Request $request, $databox_id, $record_id)
    {

        $result = new API_V1_result($this->app, $request, $this);

        $record = $this->app['phraseanet.appbox']
                    ->get_databox($databox_id)
                    ->get_record($record_id);

        $ret = [];

        $devices = $request->get('devices', []);
        $mimes = $request->get('mimes', []);

        foreach ($record->get_embedable_medias($devices, $mimes) as $name => $media) {
            $ret[] = $this->list_embedable_media($media, $this->app['phraseanet.registry']);
        }

        $result->set_datas(["embed" => $ret]);

        return $result;
    }

    public function set_record_metadatas(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $record = $this->app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        try {
            $metadatas = $request->get('metadatas');

            if (!is_array($metadatas)) {
                throw new Exception('Metadatas should be an array');
            }

            foreach ($metadatas as $metadata) {
                if (!is_array($metadata)) {
                    throw new Exception('Each Metadata value should be an array');
                }
            }

            $record->set_metadatas($metadatas);
            $result->set_datas(["record_metadatas" => $this->list_record_caption($record->get_caption())]);
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('An error occured'));
        }

        return $result;
    }

    public function set_record_status(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $databox = $this->app['phraseanet.appbox']->get_databox($databox_id);
        $record = $databox->get_record($record_id);
        $status_bits = $databox->get_statusbits();

        try {
            $status = $request->get('status');

            $datas = strrev($record->get_status());

            if (!is_array($status)) {
                throw new API_V1_exception_badrequest();
            }
            foreach ($status as $n => $value) {
                if ($n > 31 || $n < 4) {
                    throw new API_V1_exception_badrequest();
                }
                if (!in_array($value, ['0', '1'])) {
                    throw new API_V1_exception_badrequest();
                }
                if (!isset($status_bits[$n])) {
                    throw new API_V1_exception_badrequest ();
                }

                $datas = substr($datas, 0, ($n)) . $value . substr($datas, ($n + 2));
            }
            $datas = strrev($datas);

            $record->set_binary_status($datas);

            $this->app['phraseanet.SE']->updateRecord($record);

            $result->set_datas([
                "status" =>
                $this->list_record_status($databox, $record->get_status())
                    ]
            );
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('An error occured'));
        }

        return $result;
    }

    /**
     * Move a record to another collection
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $record_id
     * @return API_V1_result
     */
    public function set_record_collection(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $databox = $this->app['phraseanet.appbox']->get_databox($databox_id);
        $record = $databox->get_record($record_id);

        try {
            $collection = collection::get_from_base_id($this->app, $request->get('base_id'));

            $record->move_to_collection($collection, $this->app['phraseanet.appbox']);
            $result->set_datas(["record" => $this->list_record($record)]);
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, $e->getMessage());
        }

        return $result;
    }

    /**
     * Return detailed informations about one record
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $record_id
     * @return API_V1_result
     */
    public function get_record(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $databox = $this->app['phraseanet.appbox']->get_databox($databox_id);
        try {
            $record = $databox->get_record($record_id);
            $result->set_datas(['record' => $this->list_record($record)]);
        } catch (NotFoundHttpException $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('Record Not Found'));
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('An error occured'));
        }

        return $result;
    }

    /**
     * Return detailed informations about one story
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $story_id
     * @return API_V1_result
     */
    public function get_story(Request $request, $databox_id, $story_id)
    {
        $result = new API_V1_result($this->app, $request, $this);
        $databox = $this->app['phraseanet.appbox']->get_databox($databox_id);
        try {
            $story = $databox->get_record($story_id);
            $result->set_datas(['story' => $this->list_story($story)]);
        } catch (NotFoundHttpException $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('Story Not Found'));
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('An error occured'));
        }

        return $result;
    }

    /**
     * Return the baskets list of the authenticated user
     *
     * @param  Request       $request
     * @return API_V1_result
     */
    public function search_baskets(Request $request)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $usr_id = $this->app['authentication']->getUser()->get_id();

        $result->set_datas(['baskets' => $this->list_baskets($usr_id)]);

        return $result;
    }

    /**
     * Return a baskets list
     *
     * @param  int   $usr_id
     * @return array
     */
    protected function list_baskets($usr_id)
    {
        $repo = $this->app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket');
        /* @var $repo Alchemy\Phrasea\Model\Repositories\BasketRepository */

        $baskets = $repo->findActiveByUser($this->app['authentication']->getUser());

        $ret = [];
        foreach ($baskets as $basket) {
            $ret[] = $this->list_basket($basket);
        }

        return $ret;
    }

    /**
     * Create a new basket
     *
     * @param  Request       $request
     * @return API_V1_result
     */
    public function create_basket(Request $request)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $name = $request->get('name');

        if (trim(strip_tags($name)) === '') {
            throw new API_V1_exception_badrequest('Missing basket name parameter');
        }

        $Basket = new Basket();
        $Basket->setOwner($this->app['authentication']->getUser());
        $Basket->setName($name);

        $this->app['EM']->persist($Basket);
        $this->app['EM']->flush();

        $result->set_datas(["basket" => $this->list_basket($Basket)]);

        return $result;
    }

    /**
     * Delete a basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     * @return array
     */
    public function delete_basket(Request $request, Basket $basket)
    {
        $this->app['EM']->remove($basket);
        $this->app['EM']->flush();

        return $this->search_baskets($request);
    }

    /**
     * Retrieve a basket
     *
     * @param  Request       $request
     * @param  Basket        $basket
     * @return API_V1_result
     */
    public function get_basket(Request $request, Basket $basket)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $result->set_datas(
                [
                    "basket"          => $this->list_basket($basket),
                    "basket_elements" => $this->list_basket_content($basket)
                ]
        );

        return $result;
    }

    /**
     * Retrieve elements of one basket
     *
     * @param  Basket $Basket
     * @return type
     */
    protected function list_basket_content(Basket $Basket)
    {
        $ret = [];

        foreach ($Basket->getElements() as $basket_element) {
            $ret[] = $this->list_basket_element($basket_element);
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about a basket element
     *
     * @param  BasketElement $basket_element
     * @return type
     */
    protected function list_basket_element(BasketElement $basket_element)
    {
        $ret = [
            'basket_element_id' => $basket_element->getId(),
            'order'             => $basket_element->getOrd(),
            'record'            => $this->list_record($basket_element->getRecord($this->app)),
            'validation_item'   => null != $basket_element->getBasket()->getValidation(),
        ];

        if ($basket_element->getBasket()->getValidation()) {
            $choices = [];
            $agreement = null;
            $note = '';

            foreach ($basket_element->getValidationDatas() as $validation_datas) {
                $participant = $validation_datas->getParticipant();
                $user = $participant->getUser($this->app);
                /* @var $validation_datas Alchemy\Phrasea\Model\Entities\ValidationData */
                $choices[] = [
                    'validation_user' => [
                        'usr_id'         => $user->get_id(),
                        'usr_name'       => $user->get_display_name(),
                        'confirmed'      => $participant->getIsConfirmed(),
                        'can_agree'      => $participant->getCanAgree(),
                        'can_see_others' => $participant->getCanSeeOthers(),
                        'readonly'       => $user->get_id() != $this->app['authentication']->getUser()->get_id(),
                    ],
                    'agreement'      => $validation_datas->getAgreement(),
                    'updated_on'     => $validation_datas->getUpdated()->format(DATE_ATOM),
                    'note'           => null === $validation_datas->getNote() ? '' : $validation_datas->getNote(),
                ];

                if ($user->get_id() == $this->app['authentication']->getUser()->get_id()) {
                    $agreement = $validation_datas->getAgreement();
                    $note = null === $validation_datas->getNote() ? '' : $validation_datas->getNote();
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
     * @param  Request       $request
     * @param  Basket        $basket
     * @return API_V1_result
     */
    public function set_basket_title(Request $request, Basket $basket)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $basket->setName($request->get('name'));

        $this->app['EM']->persist($basket);
        $this->app['EM']->flush();

        $result->set_datas(["basket" => $this->list_basket($basket)]);

        return $result;
    }

    /**
     * Change the description of one basket
     *
     * @param  Request       $request
     * @param  Basket        $basket
     * @return API_V1_result
     */
    public function set_basket_description(Request $request, Basket $basket)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $basket->setDescription($request->get('description'));

        $this->app['EM']->persist($basket);
        $this->app['EM']->flush();

        $result->set_datas(["basket" => $this->list_basket($basket)]);

        return $result;
    }

    /**
     * List all avalaible feeds
     *
     * @param  Request       $request
     * @param  User_Adapter  $user
     * @return API_V1_result
     */
    public function search_publications(Request $request, User_Adapter $user)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $coll = $this->app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->getAllForUser($this->app['acl']->get($user));

        $datas = [];
        foreach ($coll as $feed) {
            $datas[] = $this->list_publication($feed, $user);
        }

        $result->set_datas(["feeds" => $datas]);

        return $result;
    }

    /**
     * @param Request $request
     * @param int     $publication_id
     */
    public function remove_publications(Request $request, $publication_id)
    {

    }

    /**
     * Retrieve one feed
     *
     * @param  Request       $request
     * @param  int           $publication_id
     * @param  User_Adapter  $user
     * @return API_V1_result
     */
    public function get_publication(Request $request, $publication_id, User_Adapter $user)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $feed = $this->app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->find($publication_id);
        if (!$feed->isAccessible($user, $this->app)) {
            return $result->set_datas([]);
        }
        $offset_start = (int) ($request->get('offset_start') ? : 0);
        $per_page = (int) ($request->get('per_page') ? : 5);

        $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 5;

        $datas = [
            'feed'         => $this->list_publication($feed, $user),
            'offset_start' => $offset_start,
            'per_page'     => $per_page,
            'entries'      => $this->list_publications_entries($feed, $offset_start, $per_page),
        ];

        $result->set_datas($datas);

        return $result;
    }

    public function get_publications(Request $request, User_Adapter $user)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $feed = Aggregate::createFromUser($this->app, $user);

        $offset_start = (int) ($request->get('offset_start') ? : 0);
        $per_page = (int) ($request->get('per_page') ? : 5);

        $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 5;

        $datas = [
            'total_entries' => $feed->getCountTotalEntries(),
            'offset_start'  => $offset_start,
            'per_page'      => $per_page,
            'entries'       => $this->list_publications_entries($feed, $offset_start, $per_page),
        ];

        $result->set_datas($datas);

        return $result;
    }

    public function get_feed_entry(Request $request, $entry_id, User_Adapter $user)
    {
        $result = new API_V1_result($this->app, $request, $this);

        $entry = $this->app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\FeedEntry')->find($entry_id);

        $collection = $entry->getFeed()->getCollection($this->app);

        if (null !== $collection && !$this->app['acl']->get($user)->has_access_to_base($collection->get_base_id())) {
            throw new \API_V1_exception_forbidden('You have not access to the parent feed');
        }

        $datas = [
            'entry' => $this->list_publication_entry($entry),
        ];

        $result->set_datas($datas);

        return $result;
    }

    /**
     * Retrieve detailled informations about one feed
     *
     * @param  Feed  $feed
     * @param  type  $user
     * @return array
     */
    protected function list_publication(Feed $feed, $user)
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

    /**
     * Retrieve all entries of one feed
     *
     * @param  FeedInterface $feed
     * @param  int           $offset_start
     * @param  int           $how_many
     * @return array
     */
    protected function list_publications_entries(FeedInterface $feed, $offset_start = 0, $how_many = 5)
    {

        $entries = $feed->getEntries($offset_start, $how_many);

        $out = [];
        foreach ($entries as $entry) {
            $out[] = $this->list_publication_entry($entry);
        }

        return $out;
    }

    /**
     * Retrieve detailled information about one feed entry
     *
     * @param  FeedEntry $entry
     * @return array
     */
    protected function list_publication_entry(FeedEntry $entry)
    {
        $items = [];
        foreach ($entry->getItems() as $item) {
            $items[] = $this->list_publication_entry_item($item);
        }

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
            'feed_url'     => '/feeds/' . $entry->getFeed()->getId() . '/content/',
            'url'          => '/feeds/entry/' . $entry->getId() . '/',
        ];
    }

    /**
     * Retrieve detailled informations about one feed  entry item
     *
     * @param  FeedItem $item
     * @return array
     */
    protected function list_publication_entry_item(FeedItem $item)
    {
        $datas = [
            'item_id' => $item->getId()
            , 'record'  => $this->list_record($item->getRecord($this->app))
        ];

        return $datas;
    }

    /**
     * @todo
     * @param Request $request
     */
    public function search_users(Request $request)
    {

    }

    /**
     * @param Request $request
     * @param int     $usr_id
     */
    public function get_user_acces(Request $request, $usr_id)
    {

    }

    /**
     * @todo
     * @param Request $request
     */
    public function add_user(Request $request)
    {

    }

    /**
     * @retrieve detailled informations about one suddef
     *
     * @param  media_subdef $media
     * @return array
     */
    protected function list_embedable_media(media_subdef $media, registryInterface $registry)
    {
        if (!$media->is_physically_present()) {
            return null;
        }

        if ($media->get_permalink() instanceof media_Permalink_Adapter) {
            $permalink = $this->list_permalink($media->get_permalink(), $registry);
        } else {
            $permalink = null;
        }

        return [
            'name'        => $media->get_name(),
            'permalink'   => $permalink,
            'height'      => $media->get_height(),
            'width'       => $media->get_width(),
            'filesize'    => $media->get_size(),
            'devices'     => $media->getDevices(),
            'player_type' => $media->get_type(),
            'mime_type'   => $media->get_mime(),
        ];
    }

    /**
     * Retrieve detailled information about one permalink
     *
     * @param  media_Permalink_Adapter $permalink
     * @param  registryInterface       $registry
     * @return type
     */
    protected function list_permalink(media_Permalink_Adapter $permalink, registryInterface $registry)
    {
        return [
            'created_on'   => $permalink->get_created_on()->format(DATE_ATOM),
            'id'           => $permalink->get_id(),
            'is_activated' => $permalink->get_is_activated(),
            'label'        => $permalink->get_label(),
            'updated_on'   => $permalink->get_last_modified()->format(DATE_ATOM),
            'page_url'     => $permalink->get_page(),
            'download_url' => $permalink->get_url() . '&download',
            'url'          => $permalink->get_url()
        ];
    }

    /**
     * Retrieve detailled information about one status
     *
     * @param  databox $databox
     * @param  string  $status
     * @return array
     */
    protected function list_record_status(databox $databox, $status)
    {
        $status = strrev($status);
        $ret = [];
        foreach ($databox->get_statusbits() as $bit => $status_datas) {
            $ret[] = ['bit'   => $bit, 'state' => !!substr($status, ($bit - 1), 1)];
        }

        return $ret;
    }

    /**
     * List all field about a specified caption
     *
     * @param  caption_record $caption
     * @return array
     */
    protected function list_record_caption(caption_record $caption)
    {
        $ret = [];
        foreach ($caption->get_fields() as $field) {
            foreach ($field->get_values() as $value) {
                $ret[] = $this->list_record_caption_field($value, $field);
            }
        }

        return $ret;
    }

    /**
     * Retrieve information about a caption field
     *
     * @param  caption_field $field
     * @return array
     */
    protected function list_record_caption_field(caption_Field_Value $value, caption_field $field)
    {
        return [
            'meta_id'           => $value->getId(),
            'meta_structure_id' => $field->get_meta_struct_id(),
            'name'              => $field->get_name(),
            'labels'           => [
                'fr' => $field->get_databox_field()->get_label('fr'),
                'en' => $field->get_databox_field()->get_label('en'),
                'de' => $field->get_databox_field()->get_label('de'),
                'nl' => $field->get_databox_field()->get_label('nl'),
            ],
            'value'             => $value->getValue(),
        ];
    }

    /**
     * Retirve information about one basket
     *
     * @param  Basket $basket
     * @return array
     */
    public function list_basket(Basket $basket)
    {
        $ret = [
            'basket_id'         => $basket->getId(),
            'created_on'        => $basket->getCreated()->format(DATE_ATOM),
            'description'       => (string) $basket->getDescription(),
            'name'              => $basket->getName(),
            'pusher_usr_id'     => $basket->getPusherId(),
            'updated_on'        => $basket->getUpdated()->format(DATE_ATOM),
            'unread'            => !$basket->getIsRead(),
            'validation_basket' => !!$basket->getValidation()
        ];

        if ($basket->getValidation()) {
            $users = [];

            foreach ($basket->getValidation()->getParticipants() as $participant) {
                /* @var $participant ValidationParticipant */
                $user = $participant->getUser($this->app);

                $users[] = [
                    'usr_id'         => $user->get_id(),
                    'usr_name'       => $user->get_display_name(),
                    'confirmed'      => $participant->getIsConfirmed(),
                    'can_agree'      => $participant->getCanAgree(),
                    'can_see_others' => $participant->getCanSeeOthers(),
                    'readonly'       => $user->get_id() != $this->app['authentication']->getUser()->get_id(),
                ];
            }

            $expires_on_atom = $basket->getValidation()->getExpires();

            if ($expires_on_atom instanceof DateTime) {
                $expires_on_atom = $expires_on_atom->format(DATE_ATOM);
            }

            $ret = array_merge(
                    [
                'validation_users'     => $users,
                'expires_on'           => $expires_on_atom,
                'validation_infos'     => $basket->getValidation()->getValidationString($this->app, $this->app['authentication']->getUser()),
                'validation_confirmed' => $basket->getValidation()->getParticipant($this->app['authentication']->getUser(), $this->app)->getIsConfirmed(),
                'validation_initiator' => $basket->getValidation()->isInitiator($this->app['authentication']->getUser()),
                    ], $ret
            );
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about one record
     *
     * @param  record_adapter $record
     * @return array
     */
    public function list_record(record_adapter $record)
    {
        $technicalInformation = [];
        foreach ($record->get_technical_infos() as $name => $value) {
            $technicalInformation[] = [
                'name'  => $name,
                'value' => $value
            ];
        }

        return [
            'databox_id'             => $record->get_sbas_id(),
            'record_id'              => $record->get_record_id(),
            'mime_type'              => $record->get_mime(),
            'title'                  => $record->get_title(),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->get_modification_date()->format(DATE_ATOM),
            'created_on'             => $record->get_creation_date()->format(DATE_ATOM),
            'collection_id'          => phrasea::collFromBas($this->app, $record->get_base_id()),
            'sha256'                 => $record->get_sha256(),
            'thumbnail'              => $this->list_embedable_media($record->get_thumbnail(), $this->app['phraseanet.registry']),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->get_type(),
            'uuid'                   => $record->get_uuid(),
        ];
    }

    /**
     * Retrieve detailled informations about one story
     *
     * @param record_adapter $story
     *
     * @return array
     */
    public function list_story(record_adapter $story)
    {
        if (!$story->is_grouping()) {
            throw new \API_V1_exception_notfound('Story not found');
        }

        $that = $this;
        $records = array_map(function (\record_adapter $record) use ($that) {
            return $that->list_record($record);
        }, array_values($story->get_children()->get_elements()));

        $caption = $story->get_caption();

        $format = function (caption_record $caption, $dcField) {

            $field = $caption->get_dc_field($dcField);

            if (!$field) {
                return null;
            }

            return $field->get_serialized_values();
        };

        return [
            '@entity@'       => self::OBJECT_TYPE_STORY,
            'databox_id'     => $story->get_sbas_id(),
            'story_id'       => $story->get_record_id(),
            'updated_on'     => $story->get_modification_date()->format(DATE_ATOM),
            'created_on'     => $story->get_creation_date()->format(DATE_ATOM),
            'collection_id'  => phrasea::collFromBas($this->app, $story->get_base_id()),
            'thumbnail'      => $this->list_embedable_media($story->get_thumbnail(), $this->app['phraseanet.registry']),
            'uuid'           => $story->get_uuid(),
            'metadatas'      => [
                '@entity@'       => self::OBJECT_TYPE_STORY_METADATA_BAG,
                'dc:contributor' => $format($caption, databox_Field_DCESAbstract::Contributor),
                'dc:coverage'    => $format($caption, databox_Field_DCESAbstract::Coverage),
                'dc:creator'     => $format($caption, databox_Field_DCESAbstract::Creator),
                'dc:date'        => $format($caption, databox_Field_DCESAbstract::Date),
                'dc:description' => $format($caption, databox_Field_DCESAbstract::Description),
                'dc:format'      => $format($caption, databox_Field_DCESAbstract::Format),
                'dc:identifier'  => $format($caption, databox_Field_DCESAbstract::Identifier),
                'dc:language'    => $format($caption, databox_Field_DCESAbstract::Language),
                'dc:publisher'   => $format($caption, databox_Field_DCESAbstract::Publisher),
                'dc:relation'    => $format($caption, databox_Field_DCESAbstract::Relation),
                'dc:rights'      => $format($caption, databox_Field_DCESAbstract::Rights),
                'dc:source'      => $format($caption, databox_Field_DCESAbstract::Source),
                'dc:subject'     => $format($caption, databox_Field_DCESAbstract::Subject),
                'dc:title'       => $format($caption, databox_Field_DCESAbstract::Title),
                'dc:type'        => $format($caption, databox_Field_DCESAbstract::Type),
            ],
            'records'        => $records,
        ];
    }

    /**
     * List all databoxes of the current appbox
     *
     * @return array
     */
    protected function list_databoxes()
    {
        $ret = [];
        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
            $ret[] = $this->list_databox($databox);
        }

        return $ret;
    }

    /**
     * Retrieve CGU's for the specified databox
     *
     * @param  databox $databox
     * @return array
     */
    protected function list_databox_terms(databox $databox)
    {
        $ret = [];
        foreach ($databox->get_cgus() as $locale => $array_terms) {
            $ret[] = ['locale' => $locale, 'terms'  => $array_terms['value']];
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about one databox
     * @param  databox $databox
     * @return array
     */
    protected function list_databox(databox $databox)
    {
        $ret = [];

        $ret['databox_id'] = $databox->get_sbas_id();
        $ret['name']       = $databox->get_dbname();
        $ret['viewname']   = $databox->get_viewname();
        $ret['labels']     = [
            'en' => $databox->get_label('en'),
            'de' => $databox->get_label('de'),
            'fr' => $databox->get_label('fr'),
            'nl' => $databox->get_label('nl'),
        ];
        $ret['version']    = $databox->get_version();

        return $ret;
    }

    /**
     * List all available collections for a specified databox
     *
     * @param  databox $databox
     * @return array
     */
    protected function list_databox_collections(databox $databox)
    {
        $ret = [];

        foreach ($databox->get_collections() as $collection) {
            $ret[] = $this->list_collection($collection);
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about one collection
     *
     * @param  collection $collection
     * @return array
     */
    protected function list_collection(collection $collection)
    {
        $ret = [
            'base_id'       => $collection->get_base_id(),
            'collection_id' => $collection->get_coll_id(),
            'name'          => $collection->get_name(),
            'labels'        => [
                'fr' => $collection->get_label('fr'),
                'en' => $collection->get_label('en'),
                'de' => $collection->get_label('de'),
                'nl' => $collection->get_label('nl'),
            ],
            'record_amount' => $collection->get_record_amount(),
        ];

        return $ret;
    }

    /**
     * Retrieve informations for a list of status
     *
     * @param  array $status
     * @return array
     */
    protected function list_databox_status(array $status)
    {
        $ret = [];
        foreach ($status as $n => $datas) {
            $ret[] = [
                'bit'        => $n,
                'label_on'   => $datas['labelon'],
                'label_off'  => $datas['labeloff'],
                'labels'     => [
                    'en' => $datas['labels_on_i18n']['en'],
                    'fr' => $datas['labels_on_i18n']['fr'],
                    'de' => $datas['labels_on_i18n']['de'],
                    'nl' => $datas['labels_on_i18n']['nl'],
                ],
                'img_on'     => $datas['img_on'],
                'img_off'    => $datas['img_off'],
                'searchable' => !!$datas['searchable'],
                'printable'  => !!$datas['printable'],
            ];
        }

        return $ret;
    }

    /**
     * List all metadatas field using a databox meta structure
     *
     * @param  databox_descriptionStructure $meta_struct
     * @return array
     */
    protected function list_databox_metadatas_fields(databox_descriptionStructure $meta_struct)
    {
        $ret = [];
        foreach ($meta_struct as $meta) {
            $ret[] = $this->list_databox_metadata_field_properties($meta);
        }

        return $ret;
    }

    /**
     * Retirve informations about one databox metadata field
     *
     * @param  databox_field $databox_field
     * @return array
     */
    protected function list_databox_metadata_field_properties(databox_field $databox_field)
    {
        $ret = [
            'id'               => $databox_field->get_id(),
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

        return $ret;
    }

}
