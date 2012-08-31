<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Border\Manager as BorderManager;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_V1_adapter extends API_V1_Abstract
{
    /**
     * API Version
     *
     * @var string
     */
    protected $version = '1.2';

    /**
     * Appbox where the API works
     *
     * @var appbox
     */
    protected $appbox;

    /**
     * Phraseanet Core
     *
     * @var \Alchemy\Phrasea\Core
     */
    protected $core;

    /**
     * API constructor
     *
     * @param  string         $auth_token Authentification Token
     * @param  appbox         $appbox     Appbox object
     * @return API_V1_adapter
     */
    public function __construct(appbox &$appbox, Alchemy\Phrasea\Core $core)
    {
        $this->appbox = $appbox;
        $this->core = $core;

        return $this;
    }

    /**
     * Retrieve  http status error code according to the message
     * @param  Request       $request
     * @param  string        $error
     * @return API_V1_result `
     */
    public function get_error_message(Request $request, $error, $message)
    {
        $result = new API_V1_result($request, $this);
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
        $result = new API_V1_result($request, $this);
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
     * @param  Application $app The silex application
     * @return \API_V1_result
     */
    public function get_scheduler(Application $app)
    {
        $result = new \API_V1_result($app['request'], $this);

        $appbox = \appbox::get_instance($app['phraseanet.core']);
        $taskManager = new \task_manager($appbox);
        $ret = $taskManager->getSchedulerState();

        $ret['state'] = $ret['status'];

        unset($ret['qdelay'], $ret['status']);

        if (null !== $ret['updated_on']) {
            $ret['updated_on'] = $ret['updated_on']->format(DATE_ATOM);
        }

        $result->set_datas(array('scheduler' => $ret));

        return $result;
    }

    /**
     * Get a list of phraseanet tasks
     *
     * @param  \Silex\Application $app The API silex application
     * @return \API_V1_result
     */
    public function get_task_list(Application $app)
    {
        $result = new \API_V1_result($app['request'], $this);

        $appbox = \appbox::get_instance($app['phraseanet.core']);
        $taskManager = new \task_manager($appbox);
        $tasks = $taskManager->getTasks();

        $ret = array();
        foreach ($tasks as $task) {
            $ret[] = $this->list_task($task);
        }

        $result->set_datas(array('tasks' => $ret));

        return $result;
    }

    protected function list_task(\task_abstract $task)
    {
        return array(
            'id'             => $task->getID(),
            'name'           => $task->getName(),
            'state'          => $task->getState(),
            'pid'            => $task->getPID(),
            'title'          => $task->getTitle(),
            'last_exec_time' => $task->getLastExecTime() ? $task->getLastExecTime()->format(DATE_ATOM) : null,
            'auto_start'     => ! ! $task->isActive(),
            'runner'         => $task->getRunner(),
            'crash_counter'  => $task->getCrashCounter()
        );
    }

    /**
     * Get informations about an identified task
     *
     * @param  \Silex\Application $app     The API silex application
     * @param  integer            $task_id
     * @return \API_V1_result
     */
    public function get_task(Application $app, $taskId)
    {
        $result = new \API_V1_result($app['request'], $this);

        $appbox = \appbox::get_instance($app['phraseanet.core']);
        $taskManager = new task_manager($appbox);

        $ret = array(
            'task' => $this->list_task($taskManager->getTask($taskId))
        );

        $result->set_datas($ret);

        return $result;
    }

    /**
     * Start a specified task
     *
     * @param  \Silex\Application $app     The API silex application
     * @param  integer            $task_id The task id
     * @return \API_V1_result
     */
    public function start_task(Application $app, $taskId)
    {
        $result = new \API_V1_result($app['request'], $this);

        $appbox = \appbox::get_instance($app['phraseanet.core']);
        $taskManager = new \task_manager($appbox);

        $task = $taskManager->getTask($taskId);
        if ( ! in_array($task->getState(), array(\task_abstract::STATE_TOSTART, \task_abstract::STATE_STARTED))) {
            $task->setState(\task_abstract::STATE_TOSTART);
        }

        $result->set_datas(array('task' => $this->list_task($task)));

        return $result;
    }

    /**
     * Stop a specified task
     *
     * @param  \Silex\Application $app     The API silex application
     * @param  integer            $task_id The task id
     * @return \API_V1_result
     */
    public function stop_task(Application $app, $taskId)
    {
        $result = new API_V1_result($app['request'], $this);

        $appbox = \appbox::get_instance($app['phraseanet.core']);
        $taskManager = new \task_manager($appbox);

        $task = $taskManager->getTask($taskId);
        if ( ! in_array($task->getState(), array(\task_abstract::STATE_TOSTOP, \task_abstract::STATE_STOPPED))) {
            $task->setState(\task_abstract::STATE_TOSTOP);
        }
        $result->set_datas(array('task' => $this->list_task($task)));

        return $result;
    }

    /**
     * Update a task property
     *  - name
     *  - autostart
     *
     * @param  \Silex\Application           $app     Silex application
     * @param  integer                      $task_id the task id
     * @return \API_V1_result
     * @throws \API_V1_exception_badrequest
     */
    public function set_task_property(Application $app, $taskId)
    {
        $result = new API_V1_result($app['request'], $this);

        $title = $app['request']->get('title');
        $autostart = $app['request']->get('autostart');

        if (null === $title && null === $autostart) {
            throw new \API_V1_exception_badrequest();
        }

        $appbox = \appbox::get_instance($app['phraseanet.core']);

        $taskManager = new \task_manager($appbox);

        $task = $taskManager->getTask($taskId);

        if ($title) {
            $task->setTitle($title);
        }

        if ($autostart) {
            $task->setActive( ! ! $autostart);
        }

        $result->set_datas(array('task' => $this->list_task($task)));

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
        $em = $app['phraseanet.core']['EM'];
        $caches = array(
            'main'               => $app['phraseanet.core']['Cache'],
            'op_code'            => $app['phraseanet.core']['OpcodeCache'],
            'doctrine_metadatas' => $em->getConfiguration()->getMetadataCacheImpl(),
            'doctrine_query'     => $em->getConfiguration()->getQueryCacheImpl(),
            'doctrine_result'    => $em->getConfiguration()->getResultCacheImpl(),
        );

        $ret = array();

        foreach ($caches as $name => $service) {
            if ($service instanceof \Alchemy\Phrasea\Cache\Cache) {
                $ret['cache'][$name] = array(
                    'type'   => $service->getName(),
                    'online' => $service->isOnline(),
                    'stats'  => $service->getStats(),
                );
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
        $ret = array();

        $ret['phraseanet']['version'] = array(
            'name'   => $app['phraseanet.core']['Version']::getName(),
            'number' => $app['phraseanet.core']['Version']::getNumber(),
        );

        $config = $app['phraseanet.core']->getConfiguration();

        $ret['phraseanet']['environment'] = $app['phraseanet.core']->getEnv();
        $ret['phraseanet']['debug'] = $config->isDebug();
        $ret['phraseanet']['maintenance'] = $config->isMaintained();
        $ret['phraseanet']['errorsLog'] = $config->isDisplayingErrors();
        $ret['phraseanet']['serverName'] = $config->getPhraseanet()->get('servername');

        return $ret;
    }

    /**
     * Provide phraseanet global values
     * @param  \Silex\Application $app the silex application
     * @return array
     */
    protected function get_gv_info(Application $app)
    {
        $registry = $app['phraseanet.core']['Registry'];

        return array(
            'global_values' => array(
                'serverName'  => $registry->get('GV_ServerName'),
                'title'       => $registry->get('GV_homeTitle'),
                'keywords'    => $registry->get('GV_metaKeywords'),
                'description' => $registry->get('GV_metaDescription'),
                'httpServer'  => array(
                    'logErrors'       => $registry->get('GV_log_errors'),
                    'phpTimezone'     => $registry->get('GV_timezone'),
                    'siteId'          => $registry->get('GV_sit'),
                    'staticUrl'       => $registry->get('GV_STATIC_URL'),
                    'defaultLanguage' => $registry->get('id_GV_default_lng'),
                    'allowIndexing'   => $registry->get('GV_allow_search_engine'),
                    'modes'           => array(
                        'XsendFile'                     => $registry->get('GV_modxsendfile'),
                        'nginxXAccelRedirect'           => $registry->get('GV_X_Accel_Redirect'),
                        'nginxXAccelRedirectMountPoint' => $registry->get('GV_X_Accel_Redirect_mount_point'),
                        'h264Streaming'                 => $registry->get('GV_h264_streaming'),
                        'authTokenDirectory'            => $registry->get('GV_mod_auth_token_directory'),
                        'authTokenDirectoryPath'        => $registry->get('GV_mod_auth_token_directory_path'),
                        'authTokenPassphrase'           => $registry->get('GV_mod_auth_token_passphrase'),
                    ),
                    'files'                         => array(
                        'owner'       => $registry->get('GV_filesOwner'),
                        'group'       => $registry->get('GV_filesOwner'),
                    )
                ),
                'maintenance' => array(
                    'alertMessage'   => $registry->get('GV_message'),
                    'displayMessage' => $registry->get('GV_message_on'),
                ),
                'webServices'    => array(
                    'googleApi'                   => $registry->get('GV_google_api'),
                    'googleAnalyticsId'           => $registry->get('GV_googleAnalytics'),
                    'googleChromeFrameDisclaimer' => $registry->get('GV_display_gcf'),
                    'i18nWebService'              => $registry->get('GV_i18n_service'),
                    'recaptacha'                  => array(
                        'active'     => $registry->get('GV_captchas'),
                        'publicKey'  => $registry->get('GV_captcha_public_key'),
                        'privateKey' => $registry->get('GV_captcha_private_key'),
                    ),
                    'youtube'    => array(
                        'active'       => $registry->get('GV_youtube_api'),
                        'clientId'     => $registry->get('GV_youtube_client_id'),
                        'clientSecret' => $registry->get('GV_youtube_client_secret'),
                        'devKey'       => $registry->get('GV_youtube_dev_key'),
                    ),
                    'flickr'       => array(
                        'active'       => $registry->get('GV_flickr_api'),
                        'clientId'     => $registry->get('GV_flickr_client_id'),
                        'clientSecret' => $registry->get('GV_flickr_client_secret'),
                    ),
                    'dailymtotion' => array(
                        'active'       => $registry->get('GV_dailymotion_api'),
                        'clientId'     => $registry->get('GV_dailymotion_client_id'),
                        'clientSecret' => $registry->get('GV_dailymotion_client_secret'),
                    )
                ),
                'navigator'    => array(
                    'active'   => $registry->get('GV_client_navigator'),
                ),
                'homepage' => array(
                    'viewType' => $registry->get('GV_home_publi'),
                ),
                'report'   => array(
                    'anonymous' => $registry->get('GV_anonymousReport'),
                ),
                'events'    => array(
                    'events'        => $registry->get('GV_events'),
                    'notifications' => $registry->get('GV_notifications'),
                ),
                'upload'        => array(
                    'allowedFileExtension' => $registry->get('GV_appletAllowedFileEx'),
                ),
                'filesystem'           => array(
                    'noWeb'        => $registry->get('GV_base_datapath_noweb'),
                ),
                'searchEngine' => array(
                    'configuration' => array(
                        'defaultQuery'     => $registry->get('GV_defaultQuery'),
                        'defaultQueryType' => $registry->get('GV_defaultQuery_type'),
                    ),
                    'sphinx'           => array(
                        'active'       => $registry->get('GV_sphinx'),
                        'host'         => $registry->get('GV_sphinx_host'),
                        'port'         => $registry->get('GV_sphinx_port'),
                        'realtimeHost' => $registry->get('GV_sphinx_rt_host'),
                        'realtimePort' => $registry->get('GV_sphinx_rt_port'),
                    ),
                    'phrasea'      => array(
                        'minChar' => $registry->get('GV_min_letters_truncation'),
                        'sort'    => $registry->get('GV_phrasea_sort'),
                    ),
                ),
                'binary'  => array(
                    'phpCli'            => $registry->get('GV_cli'),
                    'phpIni'            => $registry->get('GV_PHP_INI'),
                    'imagick'           => $registry->get('GV_imagick'),
                    'swfExtract'        => $registry->get('GV_swf_extract'),
                    'pdf2swf'           => $registry->get('GV_pdf2swf'),
                    'swfRender'         => $registry->get('GV_swf_render'),
                    'unoconv'           => $registry->get('GV_unoconv'),
                    'ffmpeg'            => $registry->get('GV_ffmpeg'),
                    'mp4box'            => $registry->get('GV_mp4box'),
                    'pdftotext'         => $registry->get('GV_pdftotext'),
                    'pdfmaxpages'       => $registry->get('GV_pdfmaxpages'),),
                'mainConfiguration' => array(
                    'adminMail'          => $registry->get('GV_adminMail'),
                    'viewBasAndCollName' => $registry->get('GV_view_bas_and_coll'),
                    'chooseExportTitle'  => $registry->get('GV_choose_export_title'),
                    'defaultExportTitle' => $registry->get('GV_default_export_title'),
                    'socialTools'        => $registry->get('GV_social_tools'),),
                'modules'            => array(
                    'thesaurus'          => $registry->get('GV_thesaurus'),
                    'storyMode'          => $registry->get('GV_multiAndReport'),
                    'docSubsitution'     => $registry->get('GV_seeOngChgDoc'),
                    'subdefSubstitution' => $registry->get('GV_seeNewThumb'),),
                'email'              => array(
                    'defaultMailAddress' => $registry->get('GV_defaulmailsenderaddr'),
                    'smtp'               => array(
                        'active'   => $registry->get('GV_smtp'),
                        'auth'     => $registry->get('GV_smtp_auth'),
                        'host'     => $registry->get('GV_smtp_host'),
                        'port'     => $registry->get('GV_smtp_port'),
                        'secure'   => $registry->get('GV_smtp_secure'),
                        'user'     => $registry->get('GV_smtp_user'),
                        'password' => $registry->get('GV_smtp_password'),
                    ),
                ),
                'ftp'      => array(
                    'active'        => $registry->get('GV_activeFTP'),
                    'activeForUser' => $registry->get('GV_ftp_for_user'),),
                'client'        => array(
                    'maxSizeDownload'         => $registry->get('GV_download_max'),
                    'tabSearchMode'           => $registry->get('GV_ong_search'),
                    'tabAdvSearchPosition'    => $registry->get('GV_ong_advsearch'),
                    'tabTopicsPosition'       => $registry->get('GV_ong_topics'),
                    'tabOngActifPosition'     => $registry->get('GV_ong_actif'),
                    'renderTopicsMode'        => $registry->get('GV_client_render_topics'),
                    'displayRolloverPreview'  => $registry->get('GV_rollover_reg_preview'),
                    'displayRolloverBasket'   => $registry->get('GV_rollover_chu'),
                    'collRenderMode'          => $registry->get('GV_client_coll_ckbox'),
                    'viewSizeBaket'           => $registry->get('GV_viewSizeBaket'),
                    'clientAutoShowProposals' => $registry->get('GV_clientAutoShowProposals'),
                    'needAuth2DL'             => $registry->get('GV_needAuth2DL'),),
                'inscription'             => array(
                    'autoSelectDB' => $registry->get('GV_autoselectDB'),
                    'autoRegister' => $registry->get('GV_autoregister'),
                ),
                'push'         => array(
                    'validationReminder' => $registry->get('GV_validation_reminder'),
                    'expirationValue'    => $registry->get('GV_val_expiration'),
                ),
            )
        );
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
        $result = new API_V1_result($app['request'], $this);

        $ret = array_merge(
            $this->get_config_info($app), $this->get_cache_info($app), $this->get_gv_info($app)
        );

        $result->set_datas($ret);

        return $result;
    }

    /**
     * Get an API_V1_result containing the databoxes
     *
     * @param  Request       $request
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_databoxes(Request $request)
    {
        $result = new API_V1_result($request, $this);

        $result->set_datas(array("databoxes" => $this->list_databoxes()));

        return $result;
    }

    /**
     * Get an API_V1_result containing the collections of a databox
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_databox_collections(Request $request, $databox_id)
    {
        $result = new API_V1_result($request, $this);

        $result->set_datas(
            array(
                "collections" => $this->list_databox_collections(
                    $this->appbox->get_databox($databox_id)
                )
            )
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the status of a databox
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_databox_status(Request $request, $databox_id)
    {
        $result = new API_V1_result($request, $this);

        $result->set_datas(
            array(
                "status" =>
                $this->list_databox_status(
                    $this->appbox->get_databox($databox_id)->get_statusbits()
                )
            )
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the metadatas of a databox
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_databox_metadatas(Request $request, $databox_id)
    {
        $result = new API_V1_result($request, $this);

        $result->set_datas(
            array(
                "document_metadatas" =>
                $this->list_databox_metadatas_fields(
                    $this->appbox->get_databox($databox_id)
                        ->get_meta_structure()
                )
            )
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the terms of use of a databox
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_databox_terms(Request $request, $databox_id)
    {
        $result = new API_V1_result($request, $this);

        $result->set_datas(
            array(
                "termsOfUse" =>
                $this->list_databox_terms($this->appbox->get_databox($databox_id))
            )
        );

        return $result;
    }

    public function caption_records(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($request, $this);

        $record = $this->appbox->get_databox($databox_id)->get_record($record_id);
        $fields = $record->get_caption()->get_fields();

        $ret = array();

        foreach ($fields as $field) {
            $ret['caption_metadatas'][] = array(
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name'              => $field->get_name(),
                'value'             => $field->get_serialized_values(";"),
            );
        }

        $result->set_datas($ret);

        return $result;
    }

    public function add_record(Application $app, Request $request)
    {
        if (count($request->files->get('file')) == 0) {
            throw new API_V1_exception_badrequest('Missing file parameter');
        }

        if ( ! $request->files->get('file') instanceof Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new API_V1_exception_badrequest('You can upload one file at time');
        }

        $file = $request->files->get('file');
        /* @var $file Symfony\Component\HttpFoundation\File\UploadedFile */

        if ( ! $file->isValid()) {
            throw new API_V1_exception_badrequest('Datas corrupted, please try again');
        }

        if ( ! $request->get('base_id')) {
            throw new API_V1_exception_badrequest('Missing base_id parameter');
        }

        $collection = \collection::get_from_base_id($request->get('base_id'));

        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_base($request->get('base_id'), 'canaddrecord')) {
            throw new API_V1_exception_forbidden(sprintf('You do not have access to collection %s', $collection->get_name()));
        }

        $media = $app['phraseanet.core']['mediavorus']->guess($file);

        $Package = new Alchemy\Phrasea\Border\File($media, $collection, $file->getClientOriginalName());

        if ($request->get('status')) {
            $Package->addAttribute(new \Alchemy\Phrasea\Border\Attribute\Status($request->get('status')));
        }

        $session = new Entities\LazaretSession();
        $session->setUsrId($app['phraseanet.core']->getAuthenticatedUser()->get_id());

        $app['phraseanet.core']['EM']->persist($session);
        $app['phraseanet.core']['EM']->flush();

        $reasons = $output = null;

        $callback = function($element, $visa, $code) use(&$reasons, &$output) {
                if ( ! $visa->isValid()) {
                    $reasons = array();

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
                throw new API_V1_exception_badrequest('Invalid forceBehavior value');
                break;
        }

        $app['phraseanet.core']['border-manager']->process($session, $Package, $callback, $behavior);

        $ret = array(
            'entity' => null,
        );

        if ($output instanceof \record_adapter) {
            $ret['entity'] = '0';
            $ret['url'] = '/records/' . $output->get_sbas_id() . '/' . $output->get_record_id() . '/';
        }
        if ($output instanceof \Entities\LazaretFile) {
            $ret['entity'] = '1';
            $ret['url'] = '/quarantine/item/' . $output->getId() . '/';
        }

        $result = new API_V1_result($request, $this);

        $result->set_datas($ret);

        return $result;
    }

    public function list_quarantine(Application $app, Request $request)
    {
        $offset_start = max($request->get('offset_start', 0), 0);
        $per_page = min(max($request->get('per_page', 10), 1), 20);

        $em = $app['phraseanet.core']->getEntityManager();
        $user = $app['phraseanet.core']->getAuthenticatedUser();
        /* @var $user \User_Adapter */
        $baseIds = array_keys($user->ACL()->get_granted_base(array('canaddrecord')));

        $lazaretFiles = array();

        if (count($baseIds) > 0) {
            $lazaretRepository = $em->getRepository('Entities\LazaretFile');

            $lazaretFiles = $lazaretRepository->findPerPage(
                $baseIds, $offset_start, $per_page
            );
        }

        $ret = array();

        foreach ($lazaretFiles as $lazaretFile) {
            $ret[] = $this->list_lazaret_file($lazaretFile);
        }

        $result = new API_V1_result($request, $this);

        $result->set_datas(array(
            'offset_start'     => $offset_start,
            'per_page'         => $per_page,
            'quarantine_items' => $ret,
        ));

        return $result;
    }

    public function list_quarantine_item($lazaret_id, Application $app, Request $request)
    {
        $lazaretFile = $app['phraseanet.core']['EM']->find('Entities\LazaretFile', $lazaret_id);

        /* @var $lazaretFile \Entities\LazaretFile */
        if (null === $lazaretFile) {
            throw new \API_V1_exception_notfound(sprintf('Lazaret file id %d not found', $lazaret_id));
        }

        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_base($lazaretFile->getBaseId(), 'canaddrecord')) {
            throw new \API_V1_exception_forbidden('You do not have access to this quarantine item');
        }

        $ret = array('quarantine_item' => $this->list_lazaret_file($lazaretFile));

        $result = new API_V1_result($request, $this);

        $result->set_datas($ret);

        return $result;
    }

    protected function list_lazaret_file(\Entities\LazaretFile $file)
    {
        $checks = array();

        if ($file->getChecks()) {
            foreach ($file->getChecks() as $checker) {

                $checks[] = $checker->getMessage();
            }
        }

        $usr_id = null;
        if ($file->getSession()->getUser()) {
            $usr_id = $file->getSession()->getUser()->get_id();
        }

        $session = array(
            'id'     => $file->getSession()->getId(),
            'usr_id' => $usr_id,
        );

        return array(
            'id'                 => $file->getId(),
            'quarantine_session' => $session,
            'base_id'            => $file->getBaseId(),
            'original_name'      => $file->getOriginalName(),
            'sha256'             => $file->getSha256(),
            'uuid'               => $file->getUuid(),
            'forced'             => $file->getForced(),
            'checks'             => $file->getForced() ? array() : $checks,
            'created_on' => $file->getCreated()->format(DATE_ATOM),
            'updated_on' => $file->getUpdated()->format(DATE_ATOM),
        );
    }

    /**
     * Get an API_V1_result containing the results of a records search
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function search_records(Request $request)
    {
        $session = $this->appbox->get_session();
        $user = User_Adapter::getInstance($session->get_usr_id(), $this->appbox);
        $registry = $this->appbox->get_registry();
        $result = new API_V1_result($request, $this);

        $search_type = ($request->get('search_type')
            && in_array($request->get('search_type'), array(0, 1))) ?
            $request->get('search_type') : 0;

        $record_type = ($request->get('record_type')
            && in_array(
                $request->get('record_type')
                , array('audio', 'video', 'image', 'document', 'flash'))
            ) ?
            $request->get('record_type') : '';

        $params = array(
            'fields' => is_array($request->get('fields')) ? $request->get('fields') : array(),
            'status' => is_array($request->get('status')) ? $request->get('status') : array(),
            'bases' => is_array($request->get('bases')) ? $request->get('bases') : array(),
            'search_type'  => $search_type,
            'recordtype'   => $record_type,
            'datemin'      => $request->get('date_min') ? : '',
            'datemax'      => $request->get('date_max') ? : '',
            'datefield'    => $request->get('date_field') ? : '',
            'sort'         => $request->get('sort') ? : '',
            'ord'          => $request->get('ord') ? : '',
            'stemme'       => $request->get('stemme') ? : '',
            'per_page'     => $request->get('per_page') ? : 10,
            'query'        => $request->get('query') ? : '',
            'offset_start' => (int) ($request->get('offset_start') ? : 0),
        );

        if (is_array($request->get('bases')) === false) {
            $params['bases'] = array();
            foreach ($this->appbox->get_databoxes() as $databox) {
                foreach ($databox->get_collections() as $collection)
                    $params['bases'][] = $collection->get_base_id();
            }
        }

        $options = new searchEngine_options();

        $params['bases'] = is_array($params['bases']) ? $params['bases'] : array_keys($user->ACL()->get_granted_base());

        /* @var $user \User_Adapter */
        if ($user->ACL()->has_right('modifyrecord')) {
            $options->set_business_fields(array());

            $BF = array();

            foreach ($user->ACL()->get_granted_base(array('canmodifrecord')) as $collection) {
                if (count($params['bases']) === 0 || in_array($collection->get_base_id(), $params['bases'])) {
                    $BF[] = $collection->get_base_id();
                }
            }
            $options->set_business_fields($BF);
        } else {
            $options->set_business_fields(array());
        }

        $options->set_bases($params['bases'], $user->ACL());

        if ( ! is_array($params['fields'])) {
            $params['fields'] = array();
        }
        $options->set_fields($params['fields']);
        if ( ! is_array($params['status'])) {
            $params['status'] = array();
        }
        $options->set_status($params['status']);
        $options->set_search_type($params['search_type']);
        $options->set_record_type($params['recordtype']);
        $options->set_min_date($params['datemin']);
        $options->set_max_date($params['datemax']);
        $options->set_date_fields(explode('|', $params['datefield']));
        $options->set_sort($params['sort'], $params['ord']);
        $options->set_use_stemming($params['stemme']);

        $perPage = (int) $params['per_page'];
        $search_engine = new searchEngine_adapter($registry);
        $search_engine->set_options($options);

        $search_engine->reset_cache();

        $search_result = $search_engine->query_per_offset($params['query'], $params["offset_start"], $perPage);

        $ret = array(
            'offset_start'      => $params["offset_start"],
            'per_page'          => $perPage,
            'available_results' => $search_result->get_count_available_results(),
            'total_results'     => $search_result->get_count_total_results(),
            'error'             => $search_result->get_error(),
            'warning'           => $search_result->get_warning(),
            'query_time'        => $search_result->get_query_time(),
            'search_indexes'    => $search_result->get_search_indexes(),
            'suggestions'       => $search_result->get_suggestions(),
            'results'           => array(),
            'query' => $search_engine->get_query(),
        );

        foreach ($search_result->get_datas()->get_elements() as $record) {
            $ret['results'][] = $this->list_record($record);
        }

        /**
         * @todo donner des highlights
         */
        $result->set_datas($ret);

        return $result;
    }

    /**
     * Get an API_V1_result containing the baskets where the record is in
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $record_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_record_related(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($request, $this);

        $containers = $this->appbox
            ->get_databox($databox_id)
            ->get_record($record_id)
            ->get_container_baskets();

        $ret = array();
        foreach ($containers as $basket) {
            $ret[] = $this->list_basket($basket);
        }

        $result->set_datas(array("baskets" => $ret));

        return $result;
    }

    /**
     * Get an API_V1_result containing the record metadatas
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $record_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_record_metadatas(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($request, $this);

        $record = $this->appbox->get_databox($databox_id)->get_record($record_id);

        $result->set_datas(
            array(
                "record_metadatas" => $this->list_record_caption($record->get_caption())
            )
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the record status
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $record_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_record_status(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($request, $this);

        $record = $this->appbox
            ->get_databox($databox_id)
            ->get_record($record_id);

        $result->set_datas(
            array(
                "status" =>
                $this->list_record_status(
                    $this->appbox->get_databox($databox_id)
                    , $record->get_status()
                )
            )
        );

        return $result;
    }

    /**
     * Get an API_V1_result containing the record embed files
     *
     * @param  Request       $request
     * @param  int           $databox_id
     * @param  int           $record_id
     * @param  string        $response_type
     * @return API_V1_result
     */
    public function get_record_embed(Request $request, $databox_id, $record_id)
    {

        $result = new API_V1_result($request, $this);

        $record = $this->appbox->get_databox($databox_id)->get_record($record_id);

        $ret = array();

        $devices = $request->get('devices', array());
        $mimes = $request->get('mimes', array());

        foreach ($record->get_embedable_medias($devices, $mimes) as $name => $media) {
            $ret[] = $this->list_embedable_media($media, $this->appbox->get_registry());
        }

        $result->set_datas(array("embed" => $ret));

        return $result;
    }

    public function set_record_metadatas(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($request, $this);
        $record = $this->appbox->get_databox($databox_id)->get_record($record_id);

        try {
            $metadatas = $request->get('metadatas');

            if ( ! is_array($metadatas)) {
                throw new Exception('Metadatas should be an array');
            }

            foreach ($metadatas as $metadata) {
                if ( ! is_array($metadata)) {
                    throw new Exception('Each Metadata value should be an array');
                }
            }

            $record->set_metadatas($metadatas);
            $result->set_datas(array("record_metadatas" => $this->list_record_caption($record->get_caption())));
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('An error occured'));
        }

        return $result;
    }

    public function set_record_status(Request $request, $databox_id, $record_id)
    {
        $result = new API_V1_result($request, $this);
        $databox = $this->appbox->get_databox($databox_id);
        $record = $databox->get_record($record_id);
        $status_bits = $databox->get_statusbits();

        try {
            $status = $request->get('status');

            $datas = strrev($record->get_status());

            if ( ! is_array($status)) {
                throw new API_V1_exception_badrequest();
            }
            foreach ($status as $n => $value) {
                if ($n > 63 || $n < 4) {
                    throw new API_V1_exception_badrequest();
                }
                if ( ! in_array($value, array('0', '1'))) {
                    throw new API_V1_exception_badrequest();
                }
                if ( ! isset($status_bits[$n])) {
                    throw new API_V1_exception_badrequest ();
                }

                $datas = substr($datas, 0, ($n - 1)) . $value . substr($datas, ($n + 1));
            }
            $datas = strrev($datas);

            $record->set_binary_status($datas);
            $result->set_datas(array(
                "status" =>
                $this->list_record_status($databox, $record->get_status())
                )
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
        $result = new API_V1_result($request, $this);
        $databox = $this->appbox->get_databox($databox_id);
        $record = $databox->get_record($record_id);

        try {
            $collection = collection::get_from_base_id($request->get('base_id'));

            $record->move_to_collection($collection, $this->appbox);
            $result->set_datas(array("record" => $this->list_record($record)));
        } catch (Exception $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('An error occured'));
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
        $result = new API_V1_result($request, $this);
        $databox = $this->appbox->get_databox($databox_id);
        try {
            $record = $databox->get_record($record_id);
            $result->set_datas(array('record' => $this->list_record($record)));
        } catch (Exception_NotFound $e) {
            $result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, _('Record Not Found'));
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
        $result = new API_V1_result($request, $this);

        $usr_id = $session = $this->appbox->get_session()->get_usr_id();

        $result->set_datas(array('baskets' => $this->list_baskets($usr_id)));

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
        $em = $this->core->getEntityManager();
        $repo = $em->getRepository('\Entities\Basket');
        /* @var $repo \Repositories\BasketRepository */

        $baskets = $repo->findActiveByUser($this->core->getAuthenticatedUser());

        $ret = array();
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
        $result = new API_V1_result($request, $this);

        $name = $request->get('name');

        if (trim(strip_tags($name)) === '') {
            throw new API_V1_exception_badrequest('Missing basket name parameter');
        }

        $user = $this->core->getAuthenticatedUser();

        $Basket = new \Entities\Basket();
        $Basket->setOwner($user);
        $Basket->setName($name);

        $em = $this->core->getEntityManager();
        $em->persist($Basket);
        $em->flush();

        $result->set_datas(array("basket" => $this->list_basket($Basket)));

        return $result;
    }

    /**
     * Delete a basket
     *
     * @param  Request $request
     * @param  int     $basket_id
     * @return array
     */
    public function delete_basket(Request $request, $basket_id)
    {
        $em = $this->core->getEntityManager();
        $repository = $em->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */

        $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), true);
        $em->remove($Basket);
        $em->flush();

        return $this->search_baskets($request);
    }

    /**
     * Retrieve a basket
     *
     * @param  Request       $request
     * @param  int           $basket_id
     * @return API_V1_result
     */
    public function get_basket(Request $request, $basket_id)
    {
        $result = new API_V1_result($request, $this);

        $em = $this->core->getEntityManager();
        $repository = $em->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */

        $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), false);

        $result->set_datas(
            array(
                "basket"          => $this->list_basket($Basket),
                "basket_elements" => $this->list_basket_content($Basket)
            )
        );

        return $result;
    }

    /**
     * Retrieve elements of one basket
     *
     * @param  \Entities\Basket $Basket
     * @return type
     */
    protected function list_basket_content(\Entities\Basket $Basket)
    {
        $ret = array();

        foreach ($Basket->getElements() as $basket_element) {
            $ret[] = $this->list_basket_element($basket_element);
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about a basket element
     *
     * @param  \Entities\BasketElement $basket_element
     * @return type
     */
    protected function list_basket_element(\Entities\BasketElement $basket_element)
    {
        $ret = array(
            'basket_element_id' => $basket_element->getId(),
            'order'             => $basket_element->getOrd(),
            'record'            => $this->list_record($basket_element->getRecord()),
            'validation_item'   => null != $basket_element->getBasket()->getValidation(),
        );

        if ($basket_element->getBasket()->getValidation()) {
            $choices = array();
            $agreement = null;
            $note = '';

            foreach ($basket_element->getValidationDatas() as $validation_datas) {
                $participant = $validation_datas->getParticipant();
                $user = $participant->getUser();
                /* @var $validation_datas Entities\ValidationData */
                $choices[] = array(
                    'validation_user' => array(
                        'usr_id'         => $user->get_id(),
                        'usr_name'       => $user->get_display_name(),
                        'confirmed'      => $participant->getIsConfirmed(),
                        'can_agree'      => $participant->getCanAgree(),
                        'can_see_others' => $participant->getCanSeeOthers(),
                        'readonly'       => $user->get_id() != $this->core->getAuthenticatedUser()->get_id(),
                    ),
                    'agreement'      => $validation_datas->getAgreement(),
                    'updated_on'     => $validation_datas->getUpdated()->format(DATE_ATOM),
                    'note'           => null === $validation_datas->getNote() ? '' : $validation_datas->getNote(),
                );

                if ($user->get_id() == $this->core->getAuthenticatedUser()->get_id()) {
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
     * @param  int           $basket_id
     * @return API_V1_result
     */
    public function set_basket_title(Request $request, $basket_id)
    {
        $result = new API_V1_result($request, $this);

        $name = $request->get('name');

        $em = $this->core->getEntityManager();
        $repository = $em->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */

        $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), true);
        $Basket->setName($name);

        $em->merge($Basket);
        $em->flush();

        $result->set_datas(array( "basket" => $this->list_basket($Basket)));

        return $result;
    }

    /**
     * Change the description of one basket
     *
     * @param  Request       $request
     * @param  type          $basket_id
     * @return API_V1_result
     */
    public function set_basket_description(Request $request, $basket_id)
    {
        $result = new API_V1_result($request, $this);

        $desc = $request->get('description');

        $em = $this->core->getEntityManager();
        $repository = $em->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */

        $Basket = $repository->findUserBasket($basket_id, $this->core->getAuthenticatedUser(), true);
        $Basket->setDescription($desc);

        $em->merge($Basket);
        $em->flush();

        $result->set_datas(array("basket" => $this->list_basket($Basket)));

        return $result;
    }

    /**
     * List all avalaible feeds
     *
     * @param  Request       $request
     * @param  User_Adapter  $user
     * @return API_V1_result
     */
    public function search_publications(Request $request, User_Adapter &$user)
    {
        $result = new API_V1_result($request, $this);

        $coll = Feed_Collection::load_all($this->appbox, $user);

        $datas = array();
        foreach ($coll->get_feeds() as $feed) {
            $datas[] = $this->list_publication($feed, $user);
        }

        $result->set_datas(array("feeds" => $datas));

        return $result;
    }

    /**
     * @todo
     *
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
    public function get_publication(Request $request, $publication_id, User_Adapter &$user)
    {
        $result = new API_V1_result($request, $this);

        $feed = Feed_Adapter::load_with_user($this->appbox, $user, $publication_id);

        $offset_start = (int) ($request->get('offset_start') ? : 0);
        $per_page = (int) ($request->get('per_page') ? : 5);

        $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 5;

        $datas = array(
            'feed'         => $this->list_publication($feed, $user),
            'offset_start' => $offset_start,
            'per_page'     => $per_page,
            'entries'      => $this->list_publications_entries($feed, $offset_start, $per_page),
        );

        $result->set_datas($datas);

        return $result;
    }

    public function get_publications(Request $request, User_Adapter &$user)
    {
        $result = new API_V1_result($request, $this);

        $feed = Feed_Aggregate::load_with_user($this->appbox, $user);

        $offset_start = (int) ($request->get('offset_start') ? : 0);
        $per_page = (int) ($request->get('per_page') ? : 5);

        $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 5;

        $datas = array(
            'total_entries' => $feed->get_count_total_entries(),
            'offset_start'  => $offset_start,
            'per_page'      => $per_page,
            'entries'       => $this->list_publications_entries($feed, $offset_start, $per_page),
        );

        $result->set_datas($datas);

        return $result;
    }

    public function get_feed_entry(Request $request, $entry_id, User_Adapter &$user)
    {
        $result = new API_V1_result($request, $this);

        $entry = Feed_Entry_Adapter::load_from_id($this->appbox, $entry_id);

        $collection = $entry->get_feed()->get_collection();

        if (null !== $collection && ! $user->ACL()->has_access_to_base($collection->get_base_id())) {
            throw new \API_V1_exception_forbidden('You have not access to the parent feed');
        }

        $datas = array(
            'entry' => $this->list_publication_entry($entry),
        );

        $result->set_datas($datas);

        return $result;
    }

    /**
     * Retrieve detailled informations about one feed
     *
     * @param  Feed_Adapter $feed
     * @param  type         $user
     * @return array
     */
    protected function list_publication(Feed_Adapter $feed, $user)
    {
        return array(
            'id'            => $feed->get_id(),
            'title'         => $feed->get_title(),
            'subtitle'      => $feed->get_subtitle(),
            'total_entries' => $feed->get_count_total_entries(),
            'icon'          => $feed->get_icon_url(),
            'public'        => $feed->is_public(),
            'readonly'      => ! $feed->is_publisher($user),
            'deletable'     => $feed->is_owner($user),
            'created_on'    => $feed->get_created_on()->format(DATE_ATOM),
            'updated_on'    => $feed->get_updated_on()->format(DATE_ATOM),
        );
    }

    /**
     * Retrieve all entries of one feed
     *
     * @param  Feed_Adapter $feed
     * @param  int          $offset_start
     * @param  int          $how_many
     * @return array
     */
    protected function list_publications_entries(Feed_Abstract $feed, $offset_start = 0, $how_many = 5)
    {

        $entries = $feed->get_entries($offset_start, $how_many)->get_entries();

        $out = array();
        foreach ($entries as $entry) {
            $out[] = $this->list_publication_entry($entry);
        }

        return $out;
    }

    /**
     * Retrieve detailled information about one feed entry
     *
     * @param  Feed_Entry_Adapter $entry
     * @return array
     */
    protected function list_publication_entry(Feed_Entry_Adapter $entry)
    {
        $items = array();
        foreach ($entry->get_content() as $item) {
            $items[] = $this->list_publication_entry_item($item);
        }

        return array(
            'id'           => $entry->get_id(),
            'author_email' => $entry->get_author_email(),
            'author_name'  => $entry->get_author_name(),
            'created_on'   => $entry->get_created_on()->format(DATE_ATOM),
            'updated_on'   => $entry->get_updated_on()->format(DATE_ATOM),
            'title'        => $entry->get_title(),
            'subtitle'     => $entry->get_subtitle(),
            'items'        => $items,
            'feed_url'     => '/feeds/' . $entry->get_feed()->get_id() . '/content/',
            'url'          => '/feeds/entry/' . $entry->get_id() . '/',
        );
    }

    /**
     * Retrieve detailled informations about one feed  entry item
     *
     * @param  Feed_Entry_Item $item
     * @return array
     */
    protected function list_publication_entry_item(Feed_Entry_Item $item)
    {
        $datas = array(
            'item_id' => $item->get_id()
            , 'record'  => $this->list_record($item->get_record())
        );

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
     * @todo
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
    protected function list_embedable_media(media_subdef &$media, registryInterface &$registry)
    {
        if ( ! $media->is_physically_present()) {
            return null;
        }

        if ($media->get_permalink() instanceof media_Permalink_Adapter) {
            $permalink = $this->list_permalink($media->get_permalink(), $registry);
        } else {
            $permalink = null;
        }

        return array(
            'name'        => $media->get_name(),
            'permalink'   => $permalink,
            'height'      => $media->get_height(),
            'width'       => $media->get_width(),
            'filesize'    => $media->get_size(),
            'devices'     => $media->getDevices(),
            'player_type' => $media->get_type(),
            'mime_type'   => $media->get_mime(),
        );
    }

    /**
     * Retrieve detailled information about one permalink
     *
     * @param  media_Permalink_Adapter $permalink
     * @param  registryInterface       $registry
     * @return type
     */
    protected function list_permalink(media_Permalink_Adapter &$permalink, registryInterface &$registry)
    {
        return array(
            'created_on'   => $permalink->get_created_on()->format(DATE_ATOM),
            'id'           => $permalink->get_id(),
            'is_activated' => $permalink->get_is_activated(),
            'label'        => $permalink->get_label(),
            'updated_on'   => $permalink->get_last_modified()->format(DATE_ATOM),
            'page_url'     => $permalink->get_page($registry),
            'url'          => $permalink->get_url($registry)
        );
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
        $ret = array();
        foreach ($databox->get_statusbits() as $bit => $status_datas) {
            $ret[] = array('bit'   => $bit, 'state' => ! ! substr($status, ($bit - 1), 1));
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
        $ret = array();
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
        return array(
            'meta_id'           => $value->getId(),
            'meta_structure_id' => $field->get_meta_struct_id(),
            'name'              => $field->get_name(),
            'value'             => $value->getValue(),
        );
    }

    /**
     * Retirve information about one basket
     *
     * @param  \Entities\Basket $basket
     * @return array
     */
    protected function list_basket(\Entities\Basket $basket)
    {
        $ret = array(
            'basket_id'         => $basket->getId(),
            'created_on'        => $basket->getCreated()->format(DATE_ATOM),
            'description'       => (string) $basket->getDescription(),
            'name'              => $basket->getName(),
            'pusher_usr_id'     => $basket->getPusherId(),
            'updated_on'        => $basket->getUpdated()->format(DATE_ATOM),
            'unread'            => ! $basket->getIsRead(),
            'validation_basket' => ! ! $basket->getValidation()
        );

        if ($basket->getValidation()) {
            $users = array();

            foreach ($basket->getValidation()->getParticipants() as $participant) {
                /* @var $participant \Entities\ValidationParticipant */
                $user = $participant->getUser();

                $users[] = array(
                    'usr_id'         => $user->get_id(),
                    'usr_name'       => $user->get_display_name(),
                    'confirmed'      => $participant->getIsConfirmed(),
                    'can_agree'      => $participant->getCanAgree(),
                    'can_see_others' => $participant->getCanSeeOthers(),
                    'readonly'       => $user->get_id() != $this->core->getAuthenticatedUser()->get_id(),
                );
            }

            $expires_on_atom = $basket->getValidation()->getExpires();

            if ($expires_on_atom instanceof DateTime) {
                $expires_on_atom = $expires_on_atom->format(DATE_ATOM);
            }

            $user = \User_Adapter::getInstance($this->appbox->get_session()->get_usr_id(), $this->appbox);

            $ret = array_merge(
                array(
                'validation_users'     => $users,
                'expires_on'           => $expires_on_atom,
                'validation_infos'     => $basket->getValidation()->getValidationString($user),
                'validation_confirmed' => $basket->getValidation()->getParticipant($user)->getIsConfirmed(),
                'validation_initiator' => $basket->getValidation()->isInitiator($user),
                ), $ret
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
    protected function list_record(record_adapter $record)
    {
        $technicalInformation = array();
        foreach ($record->get_technical_infos() as $name => $value) {
            $technicalInformation[] = array(
                'name'  => $name,
                'value' => $value
            );
        }

        return array(
            'databox_id'             => $record->get_sbas_id(),
            'record_id'              => $record->get_record_id(),
            'mime_type'              => $record->get_mime(),
            'title'                  => $record->get_title(),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->get_modification_date()->format(DATE_ATOM),
            'created_on'             => $record->get_creation_date()->format(DATE_ATOM),
            'collection_id'          => phrasea::collFromBas($record->get_base_id()),
            'sha256'                 => $record->get_sha256(),
            'thumbnail'              => $this->list_embedable_media($record->get_thumbnail(), registry::get_instance()),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->get_type(),
            'uuid'                   => $record->get_uuid(),
        );
    }

    /**
     * List all databoxes of the current appbox
     *
     * @return array
     */
    protected function list_databoxes()
    {
        $ret = array();
        foreach ($this->appbox->get_databoxes() as $databox) {
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
        $ret = array();
        foreach ($databox->get_cgus() as $locale => $array_terms) {
            $ret[] = array('locale' => $locale, 'terms'  => $array_terms['value']);
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
        $ret = array();

        $ret['databox_id'] = $databox->get_sbas_id();
        $ret['name'] = $databox->get_viewname();
        $ret['version'] = $databox->get_version();

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
        $ret = array();

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
        $ret = array(
            'base_id'       => $collection->get_base_id(),
            'collection_id' => $collection->get_coll_id(),
            'name'          => $collection->get_name(),
            'record_amount' => $collection->get_record_amount(),
        );

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
        $ret = array();
        foreach ($status as $n => $datas) {
            $ret[] = array(
                'bit'        => $n,
                'label_on'   => $datas['labelon'],
                'label_off'  => $datas['labeloff'],
                'img_on'     => $datas['img_on'],
                'img_off'    => $datas['img_off'],
                'searchable' => ! ! $datas['searchable'],
                'printable'  => ! ! $datas['printable'],
            );
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
        $ret = array();
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
        $ret = array(
            'id'               => $databox_field->get_id(),
            'namespace'        => $databox_field->get_tag()->getGroupName(),
            'source'           => $databox_field->get_tag()->getTagname(),
            'tagname'          => $databox_field->get_tag()->getName(),
            'name'             => $databox_field->get_name(),
            'separator'        => $databox_field->get_separator(),
            'thesaurus_branch' => $databox_field->get_tbranch(),
            'type'             => $databox_field->get_type(),
            'indexable'        => $databox_field->is_indexable(),
            'multivalue'       => $databox_field->is_multi(),
            'readonly'         => $databox_field->is_readonly(),
            'required'         => $databox_field->is_required(),
        );

        return $ret;
    }
}
