<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Core\Event\ChangeStatusEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeMetadataEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\RecordCreatedEvent;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Cache\Cache as CacheInterface;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\ApiOAuth2StartEvent;
use Alchemy\Phrasea\Core\Event\ApiOAuth2EndEvent;
use Alchemy\Phrasea\Model\Entities\Basket;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Manager as BorderManager;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Silex\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Entities\LazaretSession;

class V1 implements ControllerProviderInterface
{
    const VERSION = '1.4.1';

    const OBJECT_TYPE_USER = 'http://api.phraseanet.com/api/objects/user';
    const OBJECT_TYPE_STORY = 'http://api.phraseanet.com/api/objects/story';
    const OBJECT_TYPE_STORY_METADATA_BAG = 'http://api.phraseanet.com/api/objects/story-metadata-bag';

    public static $extendedContentTypes = array('json' => array('application/vnd.phraseanet.record-extended+json'), 'yaml' => array('application/vnd.phraseanet.record-extended+yaml'), 'jsonp' => array('application/vnd.phraseanet.record-extended+jsonp'),);

    public function connect(SilexApplication $app)
    {
        $app['controller.api.v1'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function ($request) use ($app) {
            return $this->authenticate($app, $request);
        })
        ;

        $controllers->after(function (Request $request, Response $response) use ($app) {
            $token = $app['session']->get('token');
            $app['manipulator.api-log']->create($token->getAccount(), $request, $response);
            $app['manipulator.api-oauth-token']->setLastUsed($token, new \DateTime());
            $app['session']->set('token', null);
            if (null !== $app['authentication']->getUser()) {
                $app['authentication']->closeAccount();
            }
        })
        ;

        $controllers->get('/monitor/scheduler/', 'controller.api.v1:get_scheduler')->before([$this, 'ensureAdmin']);

        $controllers->get('/monitor/tasks/', 'controller.api.v1:get_task_list')->before([$this, 'ensureAdmin']);

        $controllers->get('/monitor/task/{task}/', 'controller.api.v1:get_task')->convert('task', $app['converter.task-callback'])->before([$this, 'ensureAdmin'])->assert('task', '\d+');

        $controllers->post('/monitor/task/{task}/', 'controller.api.v1:set_task_property')->convert('task', $app['converter.task-callback'])->before([$this, 'ensureAdmin'])->assert('task', '\d+');

        $controllers->post('/monitor/task/{task}/start/', 'controller.api.v1:start_task')->convert('task', $app['converter.task-callback'])->before([$this, 'ensureAdmin']);

        $controllers->post('/monitor/task/{task}/stop/', 'controller.api.v1:stop_task')->convert('task', $app['converter.task-callback'])->before([$this, 'ensureAdmin']);

        $controllers->get('/monitor/phraseanet/', 'controller.api.v1:get_phraseanet_monitor')->before([$this, 'ensureAdmin']);

        $controllers->get('/databoxes/list/', 'controller.api.v1:get_databoxes');

        $controllers->get('/databoxes/{databox_id}/collections/', 'controller.api.v1:get_databox_collections')->before([$this, 'ensureAccessToDatabox'])->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/collections/', 'controller.api.v1:getBadRequest');

        $controllers->get('/databoxes/{databox_id}/status/', 'controller.api.v1:get_databox_status')->before([$this, 'ensureAccessToDatabox'])->before([$this, 'ensureCanSeeDataboxStructure'])->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/status/', 'controller.api.v1:getBadRequest');

        $controllers->get('/databoxes/{databox_id}/metadatas/', 'controller.api.v1:get_databox_metadatas')->before([$this, 'ensureAccessToDatabox'])->before([$this, 'ensureCanSeeDataboxStructure'])->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/metadatas/', 'controller.api.v1:getBadRequest');

        $controllers->get('/databoxes/{databox_id}/termsOfUse/', 'controller.api.v1:get_databox_terms')->before([$this, 'ensureAccessToDatabox'])->assert('databox_id', '\d+');

        $controllers->get('/databoxes/{any_id}/termsOfUse/', 'controller.api.v1:getBadRequest');

        $controllers->get('/quarantine/list/', 'controller.api.v1:list_quarantine');

        $controllers->get('/quarantine/item/{lazaret_id}/', 'controller.api.v1:list_quarantine_item');

        $controllers->get('/quarantine/item/{any_id}/', 'controller.api.v1:getBadRequest');

        $controllers->post('/records/add/', 'controller.api.v1:add_record');

        $controllers->match('/search/', 'controller.api.v1:search');

        $controllers->match('/records/search/', 'controller.api.v1:search_records');

        $controllers->get('/records/{databox_id}/{record_id}/caption/', 'controller.api.v1:caption_records')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/caption/', 'controller.api.v1:getBadRequest');

        $controllers->get('/records/{databox_id}/{record_id}/metadatas/', 'controller.api.v1:get_record_metadatas')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/metadatas/', 'controller.api.v1:getBadRequest');

        $controllers->get('/records/{databox_id}/{record_id}/status/', 'controller.api.v1:get_record_status')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/status/', 'controller.api.v1:getBadRequest');

        $controllers->get('/records/{databox_id}/{record_id}/related/', 'controller.api.v1:get_record_related')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/related/', 'controller.api.v1:getBadRequest');

        $controllers->get('/records/{databox_id}/{record_id}/embed/', 'controller.api.v1:get_record_embed')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/embed/', 'controller.api.v1:getBadRequest');

        $controllers->post('/records/{databox_id}/{record_id}/setmetadatas/', 'controller.api.v1:set_record_metadatas')->before([$this, 'ensureCanAccessToRecord'])->before([$this, 'ensureCanModifyRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/records/{any_id}/{anyother_id}/setmetadatas/', 'controller.api.v1:getBadRequest');

        $controllers->post('/records/{databox_id}/{record_id}/setstatus/', 'controller.api.v1:set_record_status')->before([$this, 'ensureCanAccessToRecord'])->before([$this, 'ensureCanModifyRecordStatus'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/records/{any_id}/{anyother_id}/setstatus/', 'controller.api.v1:getBadRequest');

        $controllers->post('/records/{databox_id}/{record_id}/setcollection/', 'controller.api.v1:set_record_collection')->before([$this, 'ensureCanAccessToRecord'])->before([$this, 'ensureCanMoveRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/records/{wrong_databox_id}/{wrong_record_id}/setcollection/', 'controller.api.v1:getBadRequest');

        $controllers->get('/records/{databox_id}/{record_id}/', 'controller.api.v1:get_record')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/records/{any_id}/{anyother_id}/', 'controller.api.v1:getBadRequest');

        $controllers->get('/baskets/list/', 'controller.api.v1:search_baskets');

        $controllers->post('/baskets/add/', 'controller.api.v1:create_basket');

        $controllers->get('/baskets/{basket}/content/', 'controller.api.v1:get_basket')->before($app['middleware.basket.converter'])->before($app['middleware.basket.user-access'])->assert('basket', '\d+');

        $controllers->get('/baskets/{wrong_basket}/content/', 'controller.api.v1:getBadRequest');

        $controllers->post('/baskets/{basket}/setname/', 'controller.api.v1:set_basket_title')->before($app['middleware.basket.converter'])->before($app['middleware.basket.user-is-owner'])->assert('basket', '\d+');

        $controllers->post('/baskets/{wrong_basket}/setname/', 'controller.api.v1:getBadRequest');

        $controllers->post('/baskets/{basket}/setdescription/', 'controller.api.v1:set_basket_description')->before($app['middleware.basket.converter'])->before($app['middleware.basket.user-is-owner'])->assert('basket', '\d+');

        $controllers->post('/baskets/{wrong_basket}/setdescription/', 'controller.api.v1:getBadRequest');

        $controllers->post('/baskets/{basket}/delete/', 'controller.api.v1:delete_basket')->before($app['middleware.basket.converter'])->before($app['middleware.basket.user-is-owner'])->assert('basket', '\d+');

        $controllers->post('/baskets/{wrong_basket}/delete/', 'controller.api.v1:getBadRequest');

        $controllers->get('/feeds/list/', 'controller.api.v1:search_publications');

        $controllers->get('/feeds/content/', 'controller.api.v1:get_publications');

        $controllers->get('/feeds/entry/{entry_id}/', 'controller.api.v1:get_feed_entry')->assert('entry_id', '\d+');

        $controllers->get('/feeds/entry/{entry_id}/', 'controller.api.v1:getBadRequest');

        $controllers->get('/feeds/{feed_id}/content/', 'controller.api.v1:get_publication')->assert('feed_id', '\d+');

        $controllers->get('/feeds/{wrong_feed_id}/content/', 'controller.api.v1:getBadRequest');

        $controllers->get('/stories/{databox_id}/{record_id}/embed/', 'controller.api.v1:get_story_embed')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/stories/{any_id}/{anyother_id}/embed/', 'controller.api.v1:getBadRequest');

        $controllers->get('/stories/{databox_id}/{record_id}/', 'controller.api.v1:get_story')->before([$this, 'ensureCanAccessToRecord'])->assert('databox_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/stories/{any_id}/{anyother_id}/', 'controller.api.v1:getBadRequest');

        $controllers->get('/me/', 'controller.api.v1:get_current_user');

        return $controllers;
    }

    public function getBadRequest(Application $app, Request $request, $message = '')
    {
        $response = Result::createError($request, 400, $message)->createResponse();
        $response->headers->set('X-Status-Code', $response->getStatusCode());

        return $response;
    }

    /**
     * Return an array of key-values informations about scheduler
     *
     * @param  Application $app The silex application
     *
     * @return Response
     */
    public function get_scheduler(Application $app, Request $request)
    {
        $data = $app['task-manager.live-information']->getManager();

        return Result::create($request, ['scheduler' => ['configuration' => $data['configuration'], 'state' => $data['actual'], 'status' => $data['actual'], 'pid' => $data['process-id'], 'process-id' => $data['process-id'], 'updated_on' => (new \DateTime())->format(DATE_ATOM),]])->createResponse();
    }

    /**
     * Get a list of phraseanet tasks
     *
     * @param Application $app The API silex application
     *
     * @return Response
     */
    public function get_task_list(Application $app, Request $request)
    {
        $ret = array_map(function (Task $task) use ($app) {
            return $this->list_task($app, $task);
        }, $app['repo.tasks']->findAll());

        return Result::create($request, ['tasks' => $ret])->createResponse();
    }

    private function list_task(Application $app, Task $task)
    {
        $data = $app['task-manager.live-information']->getTask($task);

        return ['id' => $task->getId(), 'title' => $task->getName(), 'name' => $task->getName(), 'state' => $task->getStatus(), 'status' => $task->getStatus(), 'actual-status' => $data['actual'], 'process-id' => $data['process-id'], 'pid' => $data['process-id'], 'jobId' => $task->getJobId(), 'period' => $task->getPeriod(), 'last_exec_time' => $task->getLastExecution() ? $task->getLastExecution()->format(DATE_ATOM) : null, 'last_execution' => $task->getLastExecution() ? $task->getLastExecution()->format(DATE_ATOM) : null, 'updated' => $task->getUpdated() ? $task->getUpdated()->format(DATE_ATOM) : null, 'created' => $task->getCreated() ? $task->getCreated()->format(DATE_ATOM) : null, 'auto_start' => $task->getStatus() === Task::STATUS_STARTED, 'crashed' => $task->getCrashed(), 'status' => $task->getStatus(),];
    }

    /**
     * Get informations about an identified task
     *
     * @param  \Silex\Application $app The API silex application
     * @param  Task               $task
     *
     * @return Response
     */
    public function get_task(Application $app, Request $request, Task $task)
    {
        return Result::create($request, ['task' => $this->list_task($app, $task)])->createResponse();
    }

    /**
     * Start a specified task
     *
     * @param  \Silex\Application $app  The API silex application
     * @param  Task               $task The task to start
     *
     * @return Response
     */
    public function start_task(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->start($task);

        return Result::create($request, ['task' => $this->list_task($app, $task)])->createResponse();
    }

    /**
     * Stop a specified task
     *
     * @param  \Silex\Application $app  The API silex application
     * @param  Task               $task The task to stop
     *
     * @return Response
     */
    public function stop_task(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->stop($task);

        return Result::create($request, ['task' => $this->list_task($app, $task)])->createResponse();
    }

    /**
     * Update a task property
     *  - name
     *  - autostart
     *
     * @param  \Silex\Application $app  Silex application
     * @param  Task               $task The task
     *
     * @return Response
     */
    public function set_task_property(Application $app, Request $request, $task)
    {
        $title = $app['request']->get('title');
        $autostart = $app['request']->get('autostart');

        if (null === $title && null === $autostart) {
            return $this->getBadRequest($app, $request);
        }

        if ($title) {
            $task->setName($title);
        }
        if ($autostart) {
            $task->setStatus(Task::STATUS_STARTED);
        }

        return Result::create($request, ['task' => $this->list_task($app, $task)])->createResponse();
    }

    /**
     * Get Information the cache system used by the instance
     *
     * @param  \Silex\Application $app the silex application
     *
     * @return array
     */
    private function get_cache_info(Application $app)
    {
        $caches = ['main' => $app['cache'], 'op_code' => $app['opcode-cache'], 'doctrine_metadatas' => $app['EM']->getConfiguration()->getMetadataCacheImpl(), 'doctrine_query' => $app['EM']->getConfiguration()->getQueryCacheImpl(), 'doctrine_result' => $app['EM']->getConfiguration()->getResultCacheImpl(),];

        $ret = [];

        foreach ($caches as $name => $service) {
            if ($service instanceof CacheInterface) {
                $ret['cache'][$name] = ['type' => $service->getName(), 'online' => $service->isOnline(), 'stats' => $service->getStats(),];
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
     *
     * @return array
     */
    private function get_config_info(Application $app)
    {
        $ret = [];

        $ret['phraseanet']['version'] = ['name' => $app['phraseanet.version']::getName(), 'number' => $app['phraseanet.version']::getNumber(),];

        $ret['phraseanet']['environment'] = $app->getEnvironment();
        $ret['phraseanet']['debug'] = $app['debug'];
        $ret['phraseanet']['maintenance'] = $app['conf']->get(['main', 'maintenance']);
        $ret['phraseanet']['errorsLog'] = $app['debug'];
        $ret['phraseanet']['serverName'] = $app['conf']->get('servername');

        return $ret;
    }

    /**
     * Provide phraseanet global values
     *
     * @param  \Silex\Application $app the silex application
     *
     * @return array
     */
    private function get_gv_info(Application $app)
    {
        try {
            $SEStatus = $app['phraseanet.SE']->getStatus();
        } catch (\RuntimeException $e) {
            $SEStatus = ['error' => $e->getMessage()];
        }

        $binaries = $app['conf']->get(['main', 'binaries']);

        return ['global_values' => ['serverName' => $app['conf']->get('servername'), 'title' => $app['conf']->get(['registry', 'general', 'title']), 'keywords' => $app['conf']->get(['registry', 'general', 'keywords']), 'description' => $app['conf']->get(['registry', 'general', 'description']), 'httpServer' => ['phpTimezone' => ini_get('date.timezone'), 'siteId' => $app['conf']->get(['main', 'key']), 'defaultLanguage' => $app['conf']->get(['languages', 'default']), 'allowIndexing' => $app['conf']->get(['registry', 'general', 'allow-indexation']), 'modes' => ['XsendFile' => $app['conf']->get(['xsendfile', 'enabled']), 'XsendFileMapping' => $app['conf']->get(['xsendfile', 'mapping']), 'h264Streaming' => $app['conf']->get(['registry', 'executables', 'h264-streaming-enabled']), 'authTokenDirectory' => $app['conf']->get(['registry', 'executables', 'auth-token-directory']), 'authTokenDirectoryPath' => $app['conf']->get(['registry', 'executables', 'auth-token-directory-path']), 'authTokenPassphrase' => $app['conf']->get(['registry', 'executables', 'auth-token-passphrase']),]], 'maintenance' => ['alertMessage' => $app['conf']->get(['registry', 'maintenance', 'message']), 'displayMessage' => $app['conf']->get(['registry', 'maintenance', 'enabled']),], 'webServices' => ['googleApi' => $app['conf']->get(['registry', 'webservices', 'google-charts-enabled']), 'googleAnalyticsId' => $app['conf']->get(['registry', 'general', 'analytics']), 'i18nWebService' => $app['conf']->get(['registry', 'webservices', 'geonames-server']), 'recaptacha' => ['active' => $app['conf']->get(['registry', 'webservices', 'captcha-enabled']), 'publicKey' => $app['conf']->get(['registry', 'webservices', 'recaptcha-public-key']), 'privateKey' => $app['conf']->get(['registry', 'webservices', 'recaptcha-private-key']),], 'youtube' => ['active' => $app['conf']->get(['main', 'bridge', 'youtube', 'enabled']), 'clientId' => $app['conf']->get(['main', 'bridge', 'youtube', 'client_id']), 'clientSecret' => $app['conf']->get(['main', 'bridge', 'youtube', 'client_secret']), 'devKey' => $app['conf']->get(['main', 'bridge', 'youtube', 'developer_key']),], 'flickr' => ['active' => $app['conf']->get(['main', 'bridge', 'flickr', 'enabled']), 'clientId' => $app['conf']->get(['main', 'bridge', 'flickr', 'client_id']), 'clientSecret' => $app['conf']->get(['main', 'bridge', 'flickr', 'client_secret']),], 'dailymtotion' => ['active' => $app['conf']->get(['main', 'bridge', 'dailymotion', 'enabled']), 'clientId' => $app['conf']->get(['main', 'bridge', 'dailymotion', 'client_id']), 'clientSecret' => $app['conf']->get(['main', 'bridge', 'dailymotion', 'client_secret']),]], 'navigator' => ['active' => $app['conf']->get(['registry', 'api-clients', 'navigator-enabled']),], 'office-plugin' => ['active' => $app['conf']->get(['registry', 'api-clients', 'office-enabled']),], 'homepage' => ['viewType' => $app['conf']->get(['registry', 'general', 'home-presentation-mode']),], 'report' => ['anonymous' => $app['conf']->get(['registry', 'modules', 'anonymous-report']),], 'storage' => ['documents' => $app['conf']->get(['main', 'storage', 'subdefs']),], 'searchEngine' => ['configuration' => ['defaultQuery' => $app['conf']->get(['registry', 'searchengine', 'default-query']), 'defaultQueryType' => $app['conf']->get(['registry', 'searchengine', 'default-query-type']), 'minChar' => $app['conf']->get(['registry', 'searchengine', 'min-letters-truncation']),], 'engine' => ['type' => $app['phraseanet.SE']->getName(), 'status' => $SEStatus, 'configuration' => $app['phraseanet.SE']->getConfigurationPanel()->getConfiguration(),],], 'binary' => ['phpCli' => isset($binaries['php_binary']) ? $binaries['php_binary'] : null, 'phpIni' => $app['conf']->get(['registry', 'executables', 'php-conf-path']), 'swfExtract' => isset($binaries['swf_extract_binary']) ? $binaries['swf_extract_binary'] : null, 'pdf2swf' => isset($binaries['pdf2swf_binary']) ? $binaries['pdf2swf_binary'] : null, 'swfRender' => isset($binaries['swf_render_binary']) ? $binaries['swf_render_binary'] : null, 'unoconv' => isset($binaries['unoconv_binary']) ? $binaries['unoconv_binary'] : null, 'ffmpeg' => isset($binaries['ffmpeg_binary']) ? $binaries['ffmpeg_binary'] : null, 'ffprobe' => isset($binaries['ffprobe_binary']) ? $binaries['ffprobe_binary'] : null, 'mp4box' => isset($binaries['mp4box_binary']) ? $binaries['mp4box_binary'] : null, 'pdftotext' => isset($binaries['pdftotext_binary']) ? $binaries['pdftotext_binary'] : null, 'recess' => isset($binaries['recess_binary']) ? $binaries['recess_binary'] : null, 'pdfmaxpages' => $app['conf']->get(['registry', 'executables', 'pdf-max-pages']),], 'mainConfiguration' => ['viewBasAndCollName' => $app['conf']->get(['registry', 'actions', 'collection-display']), 'chooseExportTitle' => $app['conf']->get(['registry', 'actions', 'export-title-choice']), 'defaultExportTitle' => $app['conf']->get(['registry', 'actions', 'default-export-title']), 'socialTools' => $app['conf']->get(['registry', 'actions', 'social-tools']),], 'modules' => ['thesaurus' => $app['conf']->get(['registry', 'modules', 'thesaurus']), 'storyMode' => $app['conf']->get(['registry', 'modules', 'stories']), 'docSubsitution' => $app['conf']->get(['registry', 'modules', 'doc-substitution']), 'subdefSubstitution' => $app['conf']->get(['registry', 'modules', 'thumb-substitution']),], 'email' => ['defaultMailAddress' => $app['conf']->get(['registry', 'email', 'emitter-email']), 'smtp' => ['active' => $app['conf']->get(['registry', 'email', 'smtp-enabled']), 'auth' => $app['conf']->get(['registry', 'email', 'smtp-auth-enabled']), 'host' => $app['conf']->get(['registry', 'email', 'smtp-host']), 'port' => $app['conf']->get(['registry', 'email', 'smtp-port']), 'secure' => $app['conf']->get(['registry', 'email', 'smtp-secure-mode']), 'user' => $app['conf']->get(['registry', 'email', 'smtp-user']), 'password' => $app['conf']->get(['registry', 'email', 'smtp-password']),],], 'ftp' => ['active' => $app['conf']->get(['registry', 'ftp', 'ftp-enabled']), 'activeForUser' => $app['conf']->get(['registry', 'ftp', 'ftp-user-access']),], 'client' => ['maxSizeDownload' => $app['conf']->get(['registry', 'actions', 'download-max-size']), 'tabSearchMode' => $app['conf']->get(['registry', 'classic', 'search-tab']), 'tabAdvSearchPosition' => $app['conf']->get(['registry', 'classic', 'adv-search-tab']), 'tabTopicsPosition' => $app['conf']->get(['registry', 'classic', 'topics-tab']), 'tabOngActifPosition' => $app['conf']->get(['registry', 'classic', 'active-tab']), 'renderTopicsMode' => $app['conf']->get(['registry', 'classic', 'render-topics']), 'displayRolloverPreview' => $app['conf']->get(['registry', 'classic', 'stories-preview']), 'displayRolloverBasket' => $app['conf']->get(['registry', 'classic', 'basket-rollover']), 'collRenderMode' => $app['conf']->get(['registry', 'classic', 'collection-presentation']), 'viewSizeBaket' => $app['conf']->get(['registry', 'classic', 'basket-size-display']), 'clientAutoShowProposals' => $app['conf']->get(['registry', 'classic', 'auto-show-proposals']), 'needAuth2DL' => $app['conf']->get(['registry', 'actions', 'auth-required-for-export']),], 'inscription' => ['autoSelectDB' => $app['conf']->get(['registry', 'registration', 'auto-select-collections']), 'autoRegister' => $app['conf']->get(['registry', 'registration', 'auto-register-enabled']),], 'push' => ['validationReminder' => $app['conf']->get(['registry', 'actions', 'validation-reminder-days']), 'expirationValue' => $app['conf']->get(['registry', 'actions', 'validation-expiration-days']),],]];
    }

    /**
     * Provide
     *  - cache information
     *  - global values informations
     *  - configuration informations
     *
     * @param  \Silex\Application $app the silex application
     *
     * @return Response
     */
    public function get_phraseanet_monitor(Application $app, Request $request)
    {
        $ret = array_merge($this->get_config_info($app), $this->get_cache_info($app), $this->get_gv_info($app));

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Result containing the \databoxes
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get_databoxes(Application $app, Request $request)
    {
        return Result::create($request, ["databoxes" => $this->list_databoxes($app)])->createResponse();
    }

    /**
     * Get a Response containing the collections of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function get_databox_collections(Application $app, Request $request, $databox_id)
    {
        $ret = ["collections" => $this->list_databox_collections($app['phraseanet.appbox']->get_databox($databox_id))];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Response containing the status of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function get_databox_status(Application $app, Request $request, $databox_id)
    {
        $ret = ["status" => $this->list_databox_status($app['phraseanet.appbox']->get_databox($databox_id)->get_statusbits())];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Response containing the metadatas of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function get_databox_metadatas(Application $app, Request $request, $databox_id)
    {
        $ret = ["document_metadatas" => $this->list_databox_metadatas_fields($app['phraseanet.appbox']->get_databox($databox_id)->get_meta_structure())];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Response containing the terms of use of a \databox
     *
     * @param Request $request
     * @param int     $databox_id
     *
     * @return Response
     */
    public function get_databox_terms(Application $app, Request $request, $databox_id)
    {
        $ret = ["termsOfUse" => $this->list_databox_terms($app['phraseanet.appbox']->get_databox($databox_id))];

        return Result::create($request, $ret)->createResponse();
    }

    public function caption_records(Application $app, Request $request, $databox_id, $record_id)
    {
        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);
        $fields = $record->get_caption()->get_fields();

        $ret = ['caption_metadatas' => array_map(function ($field) {
            return ['meta_structure_id' => $field->get_meta_struct_id(), 'name' => $field->get_name(), 'value' => $field->get_serialized_values(";"),];
        }, $fields)];

        return Result::create($request, $ret)->createResponse();
    }

    public function add_record(Application $app, Request $request)
    {
        if (count($request->files->get('file')) == 0) {
            return $this->getBadRequest($app, $request, 'Missing file parameter');
        }

        if (!$request->files->get('file') instanceof UploadedFile) {
            return $this->getBadRequest($app, $request, 'You can upload one file at time');
        }

        $file = $request->files->get('file');
        /* @var $file UploadedFile */

        if (!$file->isValid()) {
            return $this->getBadRequest($app, $request, 'Datas corrupted, please try again');
        }

        if (!$request->get('base_id')) {
            return $this->getBadRequest($app, $request, 'Missing base_id parameter');
        }

        $collection = \collection::get_from_base_id($app, $request->get('base_id'));

        if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($request->get('base_id'), 'canaddrecord')) {
            return Result::createError($request, 403, sprintf('You do not have access to collection %s', $collection->get_label($app['locale'])))->createResponse();
        }

        $media = $app['mediavorus']->guess($file->getPathname());

        $Package = new File($app, $media, $collection, $file->getClientOriginalName());

        if ($request->get('status')) {
            $Package->addAttribute(new Status($app, $request->get('status')));
        }

        $session = new LazaretSession();
        $session->setUser($app['authentication']->getUser());

        $app['EM']->persist($session);
        $app['EM']->flush();

        $reasons = $output = null;

        $callback = function ($element, $visa, $code) use ($app, &$reasons, &$output) {
            if (!$visa->isValid()) {
                $reasons = array_map(function ($response) use ($app) {
                    return $response->getMessage($app['translator']);
                }, $visa->getResponses());
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
                return $this->getBadRequest($app, $request, sprintf('Invalid forceBehavior value `%s`', $request->get('forceBehavior')));
        }

        $app['border-manager']->process($session, $Package, $callback, $behavior);

        $ret = ['entity' => null,];

        if ($output instanceof \record_adapter) {
            $ret['entity'] = '0';
            $ret['url'] = '/records/' . $output->get_sbas_id() . '/' . $output->get_record_id() . '/';

            $app['dispatcher']->dispatch(PhraseaEvents::RECORD_CREATED, new RecordCreatedEvent($output));
        }
        if ($output instanceof LazaretFile) {
            $ret['entity'] = '1';
            $ret['url'] = '/quarantine/item/' . $output->getId() . '/';
        }

        return Result::create($request, $ret)->createResponse();
    }

    public function list_quarantine(Application $app, Request $request)
    {
        $offset_start = max($request->get('offset_start', 0), 0);
        $per_page = min(max($request->get('per_page', 10), 1), 20);

        $baseIds = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base(['canaddrecord']));

        $lazaretFiles = [];

        if (count($baseIds) > 0) {
            $lazaretRepository = $app['repo.lazaret-files'];
            $lazaretFiles = iterator_to_array($lazaretRepository->findPerPage($baseIds, $offset_start, $per_page));
        }

        $ret = array_map(function ($lazaretFile) use ($app) {
            return $this->list_lazaret_file($app, $lazaretFile);
        }, $lazaretFiles);

        $ret = ['offset_start' => $offset_start, 'per_page' => $per_page, 'quarantine_items' => $ret,];

        return Result::create($request, $ret)->createResponse();
    }

    public function list_quarantine_item($lazaret_id, Application $app, Request $request)
    {
        $lazaretFile = $app['repo.lazaret-files']->find($lazaret_id);

        /* @var $lazaretFile LazaretFile */
        if (null === $lazaretFile) {
            return Result::createError($request, 404, sprintf('Lazaret file id %d not found', $lazaret_id))->createResponse();
        }

        if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($lazaretFile->getBaseId(), 'canaddrecord')) {
            return Result::createError($request, 403, 'You do not have access to this quarantine item')->createResponse();
        }

        $ret = ['quarantine_item' => $this->list_lazaret_file($app, $lazaretFile)];

        return Result::create($request, $ret)->createResponse();
    }

    private function list_lazaret_file(Application $app, LazaretFile $file)
    {
        $checks = array_map(function ($checker) use ($app) {
            return $checker->getMessage($app['translator']);
        }, iterator_to_array($file->getChecks()));

        $usr_id = $user = null;
        if ($file->getSession()->getUser()) {
            $user = $file->getSession()->getUser();
            $usr_id = $user->getId();
        }

        $session = ['id' => $file->getSession()->getId(), 'usr_id' => $usr_id, 'user' => $user ? $this->list_user($user) : null,];

        return ['id' => $file->getId(), 'quarantine_session' => $session, 'base_id' => $file->getBaseId(), 'original_name' => $file->getOriginalName(), 'sha256' => $file->getSha256(), 'uuid' => $file->getUuid(), 'forced' => $file->getForced(), 'checks' => $file->getForced() ? [] : $checks, 'created_on' => $file->getCreated()->format(DATE_ATOM), 'updated_on' => $file->getUpdated()->format(DATE_ATOM),];
    }

    /**
     * Search for results
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function search(Application $app, Request $request)
    {
        list($ret, $search_result) = $this->prepare_search_request($app, $request);

        $ret['results'] = ['records' => [], 'stories' => []];

        foreach ($search_result->getResults() as $record) {
            if ($record->is_grouping()) {
                $ret['results']['stories'][] = $this->list_story($app, $request, $record);
            } else {
                $ret['results']['records'][] = $this->list_record($app, $record);
            }
        }

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Get a Response containing the results of a records search
     *
     * Deprecated in favor of search
     *
     * @param Request $request
     *
     * @return Response
     */
    public function search_records(Application $app, Request $request)
    {
        list($ret, $search_result) = $this->prepare_search_request($app, $request);

        foreach ($search_result->getResults() as $record) {
            $ret['results'][] = $this->list_record($app, $record);
        }

        return Result::create($request, $ret)->createResponse();
    }

    private function prepare_search_request(Application $app, Request $request)
    {
        $options = SearchEngineOptions::fromRequest($app, $request);

        $offsetStart = (int) ($request->get('offset_start') ?: 0);
        $perPage = (int) $request->get('per_page') ?: 10;

        $query = (string) $request->get('query');
        $app['phraseanet.SE']->resetCache();

        $search_result = $app['phraseanet.SE']->query($query, $offsetStart, $perPage, $options);

        $app['manipulator.user']->logQuery($app['authentication']->getUser(), $search_result->getQuery());

        foreach ($options->getDataboxes() as $databox) {
            $colls = array_map(function (\collection $collection) {
                return $collection->get_coll_id();
            }, array_filter($options->getCollections(), function (\collection $collection) use ($databox) {
                return $collection->get_databox()->get_sbas_id() == $databox->get_sbas_id();
            }));

            $app['phraseanet.SE.logger']->log($databox, $search_result->getQuery(), $search_result->getTotal(), $colls);
        }

        $app['phraseanet.SE']->clearCache();

        $ret = ['offset_start' => $offsetStart, 'per_page' => $perPage, 'available_results' => $search_result->getAvailable(), 'total_results' => $search_result->getTotal(), 'error' => $search_result->getError(), 'warning' => $search_result->getWarning(), 'query_time' => $search_result->getDuration(), 'search_indexes' => $search_result->getIndexes(), 'suggestions' => array_map(function (SearchEngineSuggestion $suggestion) {
            return $suggestion->toArray();
        }, $search_result->getSuggestions()->toArray()), 'results' => [], 'query' => $search_result->getQuery(),];

        return [$ret, $search_result];
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
    public function get_record_related(Application $app, Request $request, $databox_id, $record_id)
    {
        $that = $this;
        $baskets = array_map(function (Basket $basket) use ($that, $app) {
            return $that->list_basket($app, $basket);
        }, (array) $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id)->get_container_baskets($app['EM'], $app['authentication']->getUser()));

        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $stories = array_map(function (\record_adapter $story) use ($that, $app, $request) {
            return $that->list_story($app, $request, $story);
        }, array_values($record->get_grouping_parents()->get_elements()));

        return Result::create($request, ["baskets" => $baskets, "stories" => $stories])->createResponse();
    }

    /**
     * Get a Response containing the record metadatas
     *
     * @param Request $request
     * @param int     $databox_id
     * @param int     $record_id
     *
     * @return Response
     */
    public function get_record_metadatas(Application $app, Request $request, $databox_id, $record_id)
    {
        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);
        $ret = ["record_metadatas" => $this->list_record_caption($record->get_caption())];

        return Result::create($request, $ret)->createResponse();
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
    public function get_record_status(Application $app, Request $request, $databox_id, $record_id)
    {
        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $ret = ["status" => $this->list_record_status($app['phraseanet.appbox']->get_databox($databox_id), $record->get_status())];

        return Result::create($request, $ret)->createResponse();
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
    public function get_record_embed(Application $app, Request $request, $databox_id, $record_id)
    {
        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $devices = $request->get('devices', []);
        $mimes = $request->get('mimes', []);

        $ret = array_filter(array_map(function ($media) use ($record, $app) {
            if (null !== $embed = $this->list_embedable_media($app, $record, $media)) {
                return $embed;
            }
        }, $record->get_embedable_medias($devices, $mimes)));

        return Result::create($request, ["embed" => $ret])->createResponse();
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
    public function get_story_embed(Application $app, Request $request, $databox_id, $record_id)
    {
        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

        $devices = $request->get('devices', []);
        $mimes = $request->get('mimes', []);

        $ret = array_filter(array_map(function ($media) use ($record, $app) {
            if (null !== $embed = $this->list_embedable_media($app, $record, $media)) {
                return $embed;
            }
        }, $record->get_embedable_medias($devices, $mimes)));

        return Result::create($request, ["embed" => $ret])->createResponse();
    }

    public function set_record_metadatas(Application $app, Request $request, $databox_id, $record_id)
    {
        $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);
        $metadatas = $request->get('metadatas');

        if (!is_array($metadatas)) {
            return $this->getBadRequest($app, $request, 'Metadatas should be an array');
        }

        array_walk($metadatas, function ($metadata) use ($app, $request) {
            if (!is_array($metadata)) {
                return $this->getBadRequest($app, $request, 'Each Metadata value should be an array');
            }
        });

        $record->set_metadatas($metadatas);
        $app['dispatcher']->dispatch(PhraseaEvents::RECORD_CHANGE_METADATA, new ChangeMetadataEvent($record));

        return Result::create($request, ["record_metadatas" => $this->list_record_caption($record->get_caption())])->createResponse();
    }

    public function set_record_status(Application $app, Request $request, $databox_id, $record_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);
        $record = $databox->get_record($record_id);
        $status_bits = $databox->get_statusbits();

        $status = $request->get('status');

        $datas = strrev($record->get_status());

        if (!is_array($status)) {
            return $this->getBadRequest($app, $request);
        }
        foreach ($status as $n => $value) {
            if ($n > 31 || $n < 4) {
                return $this->getBadRequest($app, $request);
            }
            if (!in_array($value, ['0', '1'])) {
                return $this->getBadRequest($app, $request);
            }
            if (!isset($status_bits[$n])) {
                return $this->getBadRequest($app, $request);
            }

            $datas = substr($datas, 0, ($n)) . $value . substr($datas, ($n + 2));
        }

        $record->set_binary_status(strrev($datas));

        $app['dispatcher']->dispatch(PhraseaEvents::RECORD_CHANGE_STATUS, new ChangeStatusEvent($record));

        $ret = ["status" => $this->list_record_status($databox, $record->get_status())];

        return Result::create($request, $ret)->createResponse();
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
    public function set_record_collection(Application $app, Request $request, $databox_id, $record_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox($databox_id);
        $record = $databox->get_record($record_id);

        try {
            $collection = \collection::get_from_base_id($app, $request->get('base_id'));
            $record->move_to_collection($collection, $app['phraseanet.appbox']);

            return Result::create($request, ["record" => $this->list_record($app, $record)])->createResponse();
        } catch (\Exception $e) {
            return $this->getBadRequest($app, $request, $e->getMessage());
        }
    }

    /**
     * Return detailed informations about one record
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function get_record(Application $app, Request $request, $databox_id, $record_id)
    {
        try {
            $record = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

            return Result::create($request, ['record' => $this->list_record($app, $record)])->createResponse();
        } catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, $app->trans('Record Not Found'))->createResponse();
        } catch (\Exception $e) {
            return $this->getBadRequest($app, $request, $app->trans('An error occured'));
        }
    }

    /**
     * Return detailed informations about one story
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function get_story(Application $app, Request $request, $databox_id, $record_id)
    {
        try {
            $story = $app['phraseanet.appbox']->get_databox($databox_id)->get_record($record_id);

            return Result::create($request, ['story' => $this->list_story($app, $request, $story)])->createResponse();
        } catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, $app->trans('Story Not Found'))->createResponse();
        } catch (\Exception $e) {
            return $this->getBadRequest($app, $request, $app->trans('An error occured'));
        }
    }

    /**
     * Return the baskets list of the authenticated user
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function search_baskets(Application $app, Request $request)
    {
        return Result::create($request, ['baskets' => $this->list_baskets($app)])->createResponse();
    }

    /**
     * Return a baskets list
     *
     * @param  int $usr_id
     *
     * @return array
     */
    private function list_baskets(Application $app)
    {
        $repo = $app['repo.baskets'];

        /* @var $repo BasketRepository */

        return array_map(function (Basket $basket) use ($app) {
            return $this->list_basket($app, $basket);
        }, $repo->findActiveByUser($app['authentication']->getUser()));
    }

    /**
     * Create a new basket
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function create_basket(Application $app, Request $request)
    {
        $name = $request->get('name');

        if (trim(strip_tags($name)) === '') {
            return $this->getBadRequest($app, $request, 'Missing basket name parameter');
        }

        $Basket = new Basket();
        $Basket->setUser($app['authentication']->getUser());
        $Basket->setName($name);

        $app['EM']->persist($Basket);
        $app['EM']->flush();

        return Result::create($request, ["basket" => $this->list_basket($app, $Basket)])->createResponse();
    }

    /**
     * Delete a basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return array
     */
    public function delete_basket(Application $app, Request $request, Basket $basket)
    {
        $app['EM']->remove($basket);
        $app['EM']->flush();

        return $this->search_baskets($app, $request);
    }

    /**
     * Retrieve a basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return Response
     */
    public function get_basket(Application $app, Request $request, Basket $basket)
    {
        $ret = ["basket" => $this->list_basket($app, $basket), "basket_elements" => $this->list_basket_content($app, $basket)];

        return Result::create($request, $ret)->createResponse();
    }

    public function get_current_user(Application $app, Request $request)
    {
        $ret = ["user" => $this->list_user($app['authentication']->getUser())];

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * Retrieve elements of one basket
     *
     * @param  Basket $Basket
     *
     * @return type
     */
    private function list_basket_content(Application $app, Basket $Basket)
    {
        return array_map(function (BasketElement $element) use ($app) {
            return $this->list_basket_element($app, $element);
        }, iterator_to_array($Basket->getElements()));
    }

    /**
     * Retrieve detailled informations about a basket element
     *
     * @param  BasketElement $basket_element
     *
     * @return type
     */
    private function list_basket_element(Application $app, BasketElement $basket_element)
    {
        $ret = ['basket_element_id' => $basket_element->getId(), 'order' => $basket_element->getOrd(), 'record' => $this->list_record($app, $basket_element->getRecord($app)), 'validation_item' => null != $basket_element->getBasket()->getValidation(),];

        if ($basket_element->getBasket()->getValidation()) {
            $choices = [];
            $agreement = null;
            $note = '';

            foreach ($basket_element->getValidationDatas() as $validation_datas) {
                $participant = $validation_datas->getParticipant();
                $user = $participant->getUser();
                /* @var $validation_datas ValidationData */
                $choices[] = ['validation_user' => ['usr_id' => $user->getId(), 'usr_name' => $user->getDisplayName(), 'confirmed' => $participant->getIsConfirmed(), 'can_agree' => $participant->getCanAgree(), 'can_see_others' => $participant->getCanSeeOthers(), 'readonly' => $user->getId() != $app['authentication']->getUser()->getId(), 'user' => $this->list_user($user),], 'agreement' => $validation_datas->getAgreement(), 'updated_on' => $validation_datas->getUpdated()->format(DATE_ATOM), 'note' => null === $validation_datas->getNote() ? '' : $validation_datas->getNote(),];

                if ($user->getId() == $app['authentication']->getUser()->getId()) {
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
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return Response
     */
    public function set_basket_title(Application $app, Request $request, Basket $basket)
    {
        $basket->setName($request->get('name'));

        $app['EM']->persist($basket);
        $app['EM']->flush();

        return Result::create($request, ["basket" => $this->list_basket($app, $basket)])->createResponse();
    }

    /**
     * Change the description of one basket
     *
     * @param  Request $request
     * @param  Basket  $basket
     *
     * @return Response
     */
    public function set_basket_description(Application $app, Request $request, Basket $basket)
    {
        $basket->setDescription($request->get('description'));

        $app['EM']->persist($basket);
        $app['EM']->flush();

        return Result::create($request, ["basket" => $this->list_basket($app, $basket)])->createResponse();
    }

    /**
     * List all avalaible feeds
     *
     * @param  Request $request
     * @param  User    $user
     *
     * @return Response
     */
    public function search_publications(Application $app, Request $request)
    {
        $user = $app['authentication']->getUser();
        $coll = $app['repo.feeds']->getAllForUser($app['acl']->get($user));

        $data = array_map(function ($feed) use ($user) {
            return $this->list_publication($feed, $user);
        }, $coll);

        return Result::create($request, ["feeds" => $data])->createResponse();
    }

    /**
     * Retrieve one feed
     *
     * @param  Request $request
     * @param  int     $publication_id
     * @param  User    $user
     *
     * @return Response
     */
    public function get_publication(Application $app, Request $request, $feed_id)
    {
        $user = $app['authentication']->getUser();
        $feed = $app['repo.feeds']->find($feed_id);

        if (!$feed->isAccessible($user, $app)) {
            return Result::create($request, [])->createResponse();
        }

        $offset_start = (int) $request->get('offset_start', 0);
        $per_page = (int) $request->get('per_page', 5);

        $per_page = (($per_page >= 1) && ($per_page <= 100)) ? $per_page : 100;

        $data = ['feed' => $this->list_publication($feed, $user), 'offset_start' => $offset_start, 'per_page' => $per_page, 'entries' => $this->list_publications_entries($app, $feed, $offset_start, $per_page),];

        return Result::create($request, $data)->createResponse();
    }

    public function get_publications(Application $app, Request $request)
    {
        $user = $app['authentication']->getUser();
        $restrictions = (array) ($request->get('feeds') ?: []);

        $feed = Aggregate::createFromUser($app, $user, $restrictions);

        $offset_start = (int) ($request->get('offset_start') ?: 0);
        $per_page = (int) ($request->get('per_page') ?: 5);

        $per_page = (($per_page >= 1) && ($per_page <= 20)) ? $per_page : 20;

        $data = ['total_entries' => $feed->getCountTotalEntries(), 'offset_start' => $offset_start, 'per_page' => $per_page, 'entries' => $this->list_publications_entries($app, $feed, $offset_start, $per_page),];

        return Result::create($request, $data)->createResponse();
    }

    public function get_feed_entry(Application $app, Request $request, $entry_id)
    {
        $user = $app['authentication']->getUser();
        $entry = $app['repo.feed-entries']->find($entry_id);
        $collection = $entry->getFeed()->getCollection($app);

        if (null !== $collection && !$app['acl']->get($user)->has_access_to_base($collection->get_base_id())) {
            return Result::createError($request, 403, 'You have not access to the parent feed')->createResponse();
        }

        return Result::create($request, ['entry' => $this->list_publication_entry($app, $entry)])->createResponse();
    }

    /**
     * Retrieve detailled informations about one feed
     *
     * @param  Feed $feed
     * @param  type $user
     *
     * @return array
     */
    private function list_publication(Feed $feed, $user)
    {
        return ['id' => $feed->getId(), 'title' => $feed->getTitle(), 'subtitle' => $feed->getSubtitle(), 'total_entries' => $feed->getCountTotalEntries(), 'icon' => $feed->getIconUrl(), 'public' => $feed->isPublic(), 'readonly' => !$feed->isPublisher($user), 'deletable' => $feed->isOwner($user), 'created_on' => $feed->getCreatedOn()->format(DATE_ATOM), 'updated_on' => $feed->getUpdatedOn()->format(DATE_ATOM),];
    }

    /**
     * Retrieve all entries of one feed
     *
     * @param  FeedInterface $feed
     * @param  int           $offset_start
     * @param  int           $how_many
     *
     * @return array
     */
    private function list_publications_entries(Application $app, FeedInterface $feed, $offset_start = 0, $how_many = 5)
    {
        return array_map(function ($entry) use ($app) {
            return $this->list_publication_entry($app, $entry);
        }, $feed->getEntries($offset_start, $how_many));
    }

    /**
     * Retrieve detailled information about one feed entry
     *
     * @param  FeedEntry $entry
     *
     * @return array
     */
    private function list_publication_entry(Application $app, FeedEntry $entry)
    {
        $items = array_map(function ($item) use ($app) {
            return $this->list_publication_entry_item($app, $item);
        }, iterator_to_array($entry->getItems()));

        return ['id' => $entry->getId(), 'author_email' => $entry->getAuthorEmail(), 'author_name' => $entry->getAuthorName(), 'created_on' => $entry->getCreatedOn()->format(DATE_ATOM), 'updated_on' => $entry->getUpdatedOn()->format(DATE_ATOM), 'title' => $entry->getTitle(), 'subtitle' => $entry->getSubtitle(), 'items' => $items, 'feed_id' => $entry->getFeed()->getId(), 'feed_title' => $entry->getFeed()->getTitle(), 'feed_url' => '/feeds/' . $entry->getFeed()->getId() . '/content/', 'url' => '/feeds/entry/' . $entry->getId() . '/',];
    }

    /**
     * Retrieve detailled informations about one feed  entry item
     *
     * @param  FeedItem $item
     *
     * @return array
     */
    private function list_publication_entry_item(Application $app, FeedItem $item)
    {
        return ['item_id' => $item->getId(), 'record' => $this->list_record($app, $item->getRecord($app)),];
    }

    /**
     * @retrieve detailled informations about one suddef
     *
     * @param  media_subdef $media
     *
     * @return array
     */
    private function list_embedable_media(Application $app, \record_adapter $record, \media_subdef $media)
    {
        if (!$media->is_physically_present()) {
            return null;
        }

        if ($app['authentication']->isAuthenticated()) {
            if ($media->get_name() !== 'document' && false === $app['acl']->get($app['authentication']->getUser())->has_access_to_subdef($record, $media->get_name())) {
                return null;
            }
            if ($media->get_name() === 'document' && !$app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'candwnldhd') && !$app['acl']->get($app['authentication']->getUser())->has_hd_grant($record)) {
                return null;
            }
        }

        if ($media->get_permalink() instanceof \media_Permalink_Adapter) {
            $permalink = $this->list_permalink($media->get_permalink());
        } else {
            $permalink = null;
        }

        return ['name' => $media->get_name(), 'permalink' => $permalink, 'height' => $media->get_height(), 'width' => $media->get_width(), 'filesize' => $media->get_size(), 'devices' => $media->getDevices(), 'player_type' => $media->get_type(), 'mime_type' => $media->get_mime(),];
    }

    /**
     * Retrieve detailled information about one permalink
     *
     * @param media_Permalink_Adapter $permalink
     *
     * @return type
     */
    private function list_permalink(\media_Permalink_Adapter $permalink)
    {
        $downloadUrl = $permalink->get_url();
        $downloadUrl->getQuery()->set('download', '1');

        return ['created_on' => $permalink->get_created_on()->format(DATE_ATOM), 'id' => $permalink->get_id(), 'is_activated' => $permalink->get_is_activated(), /** @Ignore */
            'label' => $permalink->get_label(), 'updated_on' => $permalink->get_last_modified()->format(DATE_ATOM), 'page_url' => $permalink->get_page(), 'download_url' => (string) $downloadUrl, 'url' => (string) $permalink->get_url()];
    }

    /**
     * Retrieve detailled information about one status
     *
     * @param  \databox $databox
     * @param  string   $status
     *
     * @return array
     */
    private function list_record_status(\databox $databox, $status)
    {
        $status = strrev($status);

        $ret = [];
        foreach ($databox->get_statusbits() as $bit => $status_datas) {
            $ret[] = ['bit' => $bit, 'state' => !!substr($status, ($bit - 1), 1)];
        }

        return $ret;
    }

    /**
     * List all field about a specified caption
     *
     * @param  caption_record $caption
     *
     * @return array
     */
    private function list_record_caption(\caption_record $caption)
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
     *
     * @return array
     */
    private function list_record_caption_field(\caption_Field_Value $value, \caption_field $field)
    {
        return ['meta_id' => $value->getId(), 'meta_structure_id' => $field->get_meta_struct_id(), 'name' => $field->get_name(), 'labels' => ['fr' => $field->get_databox_field()->get_label('fr'), 'en' => $field->get_databox_field()->get_label('en'), 'de' => $field->get_databox_field()->get_label('de'), 'nl' => $field->get_databox_field()->get_label('nl'),], 'value' => $value->getValue(),];
    }

    /**
     * Retrieve information about one basket
     *
     * @param  Basket $basket
     *
     * @return array
     */
    private function list_basket(Application $app, Basket $basket)
    {
        $ret = ['basket_id' => $basket->getId(), 'owner' => $this->list_user($basket->getUser()), 'created_on' => $basket->getCreated()->format(DATE_ATOM), 'description' => (string) $basket->getDescription(), 'name' => $basket->getName(), 'pusher_usr_id' => $basket->getPusher() ? $basket->getPusher()->getId() : null, 'pusher' => $basket->getPusher() ? $this->list_user($basket->getPusher()) : null, 'updated_on' => $basket->getUpdated()->format(DATE_ATOM), 'unread' => !$basket->getIsRead(), 'validation_basket' => !!$basket->getValidation()];

        if ($basket->getValidation()) {
            $users = array_map(function ($participant) use ($app) {
                $user = $participant->getUser();

                return ['usr_id' => $user->getId(), 'usr_name' => $user->getDisplayName(), 'confirmed' => $participant->getIsConfirmed(), 'can_agree' => $participant->getCanAgree(), 'can_see_others' => $participant->getCanSeeOthers(), 'readonly' => $user->getId() != $app['authentication']->getUser()->getId(), 'user' => $this->list_user($user),];
            }, iterator_to_array($basket->getValidation()->getParticipants()));

            $expires_on_atom = $basket->getValidation()->getExpires();

            if ($expires_on_atom instanceof \DateTime) {
                $expires_on_atom = $expires_on_atom->format(DATE_ATOM);
            }

            $ret = array_merge(['validation_users' => $users, 'expires_on' => $expires_on_atom, 'validation_infos' => $basket->getValidation()->getValidationString($app, $app['authentication']->getUser()), 'validation_confirmed' => $basket->getValidation()->getParticipant($app['authentication']->getUser())->getIsConfirmed(), 'validation_initiator' => $basket->getValidation()->isInitiator($app['authentication']->getUser()), 'validation_initiator_user' => $this->list_user($basket->getValidation()->getInitiator()),], $ret);
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about one record
     *
     * @param  \record_adapter $record
     *
     * @return array
     */
    public function list_record(Application $app, \record_adapter $record)
    {
        $technicalInformation = [];
        foreach ($record->get_technical_infos() as $name => $value) {
            $technicalInformation[] = ['name' => $name, 'value' => $value];
        }

        return ['databox_id' => $record->get_sbas_id(), 'record_id' => $record->get_record_id(), 'mime_type' => $record->get_mime(), 'title' => $record->get_title(), 'original_name' => $record->get_original_name(), 'updated_on' => $record->get_modification_date()->format(DATE_ATOM), 'created_on' => $record->get_creation_date()->format(DATE_ATOM), 'collection_id' => \phrasea::collFromBas($app, $record->get_base_id()), 'sha256' => $record->get_sha256(), 'thumbnail' => $this->list_embedable_media($app, $record, $record->get_thumbnail()), 'technical_informations' => $technicalInformation, 'phrasea_type' => $record->get_type(), 'uuid' => $record->get_uuid(),];
    }

    /**
     * Retrieve detailled informations about one story
     *
     * @param \record_adapter $story
     *
     * @return array
     */
    public function list_story(Application $app, Request $request, \record_adapter $story)
    {
        if (!$story->is_grouping()) {
            return Result::createError($request, 404, 'Story not found')->createResponse();
        }

        $that = $this;
        $records = array_map(function (\record_adapter $record) use ($that, $app) {
            return $that->list_record($app, $record);
        }, array_values($story->get_children()->get_elements()));

        $caption = $story->get_caption();

        $format = function (\caption_record $caption, $dcField) {

            $field = $caption->get_dc_field($dcField);

            if (!$field) {
                return null;
            }

            return $field->get_serialized_values();
        };

        return ['@entity@' => self::OBJECT_TYPE_STORY, 'databox_id' => $story->get_sbas_id(), 'story_id' => $story->get_record_id(), 'updated_on' => $story->get_modification_date()->format(DATE_ATOM), 'created_on' => $story->get_creation_date()->format(DATE_ATOM), 'collection_id' => \phrasea::collFromBas($app, $story->get_base_id()), 'thumbnail' => $this->list_embedable_media($app, $story, $story->get_thumbnail()), 'uuid' => $story->get_uuid(), 'metadatas' => ['@entity@' => self::OBJECT_TYPE_STORY_METADATA_BAG, 'dc:contributor' => $format($caption, \databox_Field_DCESAbstract::Contributor), 'dc:coverage' => $format($caption, \databox_Field_DCESAbstract::Coverage), 'dc:creator' => $format($caption, \databox_Field_DCESAbstract::Creator), 'dc:date' => $format($caption, \databox_Field_DCESAbstract::Date), 'dc:description' => $format($caption, \databox_Field_DCESAbstract::Description), 'dc:format' => $format($caption, \databox_Field_DCESAbstract::Format), 'dc:identifier' => $format($caption, \databox_Field_DCESAbstract::Identifier), 'dc:language' => $format($caption, \databox_Field_DCESAbstract::Language), 'dc:publisher' => $format($caption, \databox_Field_DCESAbstract::Publisher), 'dc:relation' => $format($caption, \databox_Field_DCESAbstract::Relation), 'dc:rights' => $format($caption, \databox_Field_DCESAbstract::Rights), 'dc:source' => $format($caption, \databox_Field_DCESAbstract::Source), 'dc:subject' => $format($caption, \databox_Field_DCESAbstract::Subject), 'dc:title' => $format($caption, \databox_Field_DCESAbstract::Title), 'dc:type' => $format($caption, \databox_Field_DCESAbstract::Type),], 'records' => $records,];
    }

    /**
     * List all \databoxes of the current appbox
     *
     * @return array
     */
    private function list_databoxes(Application $app)
    {
        return array_map(function (\databox $databox) {
            return $this->list_databox($databox);
        }, $app['phraseanet.appbox']->get_databoxes());
    }

    /**
     * Retrieve CGU's for the specified \databox
     *
     * @param  \databox $databox
     *
     * @return array
     */
    private function list_databox_terms(\databox $databox)
    {
        $ret = [];
        foreach ($databox->get_cgus() as $locale => $array_terms) {
            $ret[] = ['locale' => $locale, 'terms' => $array_terms['value']];
        }

        return $ret;
    }

    /**
     * Retrieve detailled informations about one \databox
     *
     * @param  \databox $databox
     *
     * @return array
     */
    private function list_databox(\databox $databox)
    {
        return ['databox_id' => $databox->get_sbas_id(), 'name' => $databox->get_dbname(), 'viewname' => $databox->get_viewname(), 'labels' => ['en' => $databox->get_label('en'), 'de' => $databox->get_label('de'), 'fr' => $databox->get_label('fr'), 'nl' => $databox->get_label('nl'),], 'version' => $databox->get_version(),];
    }

    /**
     * List all available collections for a specified \databox
     *
     * @param  \databox $databox
     *
     * @return array
     */
    private function list_databox_collections(\databox $databox)
    {
        return array_map(function (\collection $collection) {
            return $this->list_collection($collection);
        }, $databox->get_collections());
    }

    /**
     * Retrieve detailled informations about one collection
     *
     * @param  collection $collection
     *
     * @return array
     */
    private function list_collection(\collection $collection)
    {
        return ['base_id' => $collection->get_base_id(), 'collection_id' => $collection->get_coll_id(), 'name' => $collection->get_name(), 'labels' => ['fr' => $collection->get_label('fr'), 'en' => $collection->get_label('en'), 'de' => $collection->get_label('de'), 'nl' => $collection->get_label('nl'),], 'record_amount' => $collection->get_record_amount(),];
    }

    /**
     * Retrieve informations for a list of status
     *
     * @param  array $status
     *
     * @return array
     */
    private function list_databox_status(array $status)
    {
        $ret = [];
        foreach ($status as $n => $datas) {
            $ret[] = ['bit' => $n, 'label_on' => $datas['labelon'], 'label_off' => $datas['labeloff'], 'labels' => ['en' => $datas['labels_on_i18n']['en'], 'fr' => $datas['labels_on_i18n']['fr'], 'de' => $datas['labels_on_i18n']['de'], 'nl' => $datas['labels_on_i18n']['nl'],], 'img_on' => $datas['img_on'], 'img_off' => $datas['img_off'], 'searchable' => !!$datas['searchable'], 'printable' => !!$datas['printable'],];
        }

        return $ret;
    }

    /**
     * List all metadatas field using a \databox meta structure
     *
     * @param  \databox_descriptionStructure $meta_struct
     *
     * @return array
     */
    private function list_databox_metadatas_fields(\databox_descriptionStructure $meta_struct)
    {
        return array_map(function ($meta) {
            return $this->list_databox_metadata_field_properties($meta);
        }, iterator_to_array($meta_struct));
    }

    /**
     * Retrieve informations about one \databox metadata field
     *
     * @param  \databox_field $databox_field
     *
     * @return array
     */
    private function list_databox_metadata_field_properties(\databox_field $databox_field)
    {
        return ['id' => $databox_field->get_id(), 'namespace' => $databox_field->get_tag()->getGroupName(), 'source' => $databox_field->get_tag()->getTagname(), 'tagname' => $databox_field->get_tag()->getName(), 'name' => $databox_field->get_name(), 'labels' => ['fr' => $databox_field->get_label('fr'), 'en' => $databox_field->get_label('en'), 'de' => $databox_field->get_label('de'), 'nl' => $databox_field->get_label('nl'),], 'separator' => $databox_field->get_separator(), 'thesaurus_branch' => $databox_field->get_tbranch(), 'type' => $databox_field->get_type(), 'indexable' => $databox_field->is_indexable(), 'multivalue' => $databox_field->is_multi(), 'readonly' => $databox_field->is_readonly(), 'required' => $databox_field->is_required(),];
    }

    private function authenticate(Application $app, Request $request)
    {
        $context = new Context(Context::CONTEXT_OAUTH2_TOKEN);

        $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));
        $app['dispatcher']->dispatch(PhraseaEvents::API_OAUTH2_START, new ApiOAuth2StartEvent());

        $app['oauth2-server']->verifyAccessToken();

        if (null === $token = $app['repo.api-oauth-tokens']->find($app['oauth2-server']->getToken())) {
            throw new NotFoundHttpException('Provided token is not valid.');
        }
        $app['session']->set('token', $token);

        $oAuth2Account = $token->getAccount();
        $oAuth2App = $oAuth2Account->getApplication();

        if ($oAuth2App->getClientId() == \API_OAuth2_Application_Navigator::CLIENT_ID && !$app['conf']->get(['registry', 'api-clients', 'navigator-enabled'])) {
            return Result::createError($request, 403, 'The use of Phraseanet Navigator is not allowed')->createResponse();
        }

        if ($oAuth2App->getClientId() == \API_OAuth2_Application_OfficePlugin::CLIENT_ID && !$app['conf']->get(['registry', 'api-clients', 'office-enabled'])) {
            return Result::createError($request, 403, 'The use of Office Plugin is not allowed.')->createResponse();
        }

        $app['authentication']->openAccount($oAuth2Account->getUser());
        $app['oauth2-server']->rememberSession($app['session']);
        $app['dispatcher']->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());
    }

    public function ensureAdmin(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        if (!$user->isAdmin()) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    public function ensureAccessToDatabox(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        $databox = $app['phraseanet.appbox']->get_databox($request->attributes->get('databox_id'));

        if (!$app['acl']->get($user)->has_access_to_sbas($databox->get_sbas_id())) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    public function ensureCanAccessToRecord(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        $record = $app['phraseanet.appbox']->get_databox($request->attributes->get('databox_id'))->get_record($request->attributes->get('record_id'));
        if (!$app['acl']->get($user)->has_access_to_record($record)) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    public function ensureCanModifyRecord(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        if (!$app['acl']->get($user)->has_right('modifyrecord')) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    public function ensureCanModifyRecordStatus(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        $record = $app['phraseanet.appbox']->get_databox($request->attributes->get('databox_id'))->get_record($request->attributes->get('record_id'));
        if (!$app['acl']->get($user)->has_right_on_base($record->get_base_id(), 'chgstatus')) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    public function ensureCanSeeDataboxStructure(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        $databox = $app['phraseanet.appbox']->get_databox($request->attributes->get('databox_id'));
        if (!$app['acl']->get($user)->has_right_on_sbas($databox->get_sbas_id(), 'bas_modify_struct')) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    public function ensureCanMoveRecord(Request $request, Application $app)
    {
        $user = $app['session']->get('token')->getAccount()->getUser();
        $record = $app['phraseanet.appbox']->get_databox($request->attributes->get('databox_id'))->get_record($request->attributes->get('record_id'));
        if ((!$app['acl']->get($user)->has_right('addrecord') && !$app['acl']->get($user)->has_right('deleterecord')) || !$app['acl']->get($user)->has_right_on_base($record->get_base_id(), 'candeleterecord')) {
            return Result::createError($request, 401, 'You are not authorized')->createResponse();
        }
    }

    private function list_user(User $user)
    {
        switch ($user->getGender()) {
            case User::GENDER_MR;
                $gender = 'Mr';
                break;
            case User::GENDER_MRS;
                $gender = 'Mrs';
                break;
            case User::GENDER_MISS;
                $gender = 'Miss';
                break;
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
            'last_connection' => $user->getLastConnection() ? $user->getLastConnection()->format(DATE_ATOM) : null,
            'created_on'      => $user->getCreated() ? $user->getCreated()->format(DATE_ATOM) : null,
            'updated_on'      => $user->getUpdated() ? $user->getUpdated()->format(DATE_ATOM) : null,
            'locale'          => $user->getLocale() ?: null,
        ];
    }
}