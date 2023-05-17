<?php

namespace Alchemy\Phrasea\WorkerManager\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Form\WorkerConfigurationType;
use Alchemy\Phrasea\WorkerManager\Form\WorkerFtpType;
use Alchemy\Phrasea\WorkerManager\Form\WorkerRecordsActionsType;
use Alchemy\Phrasea\WorkerManager\Form\WorkerSearchengineType;
use Alchemy\Phrasea\WorkerManager\Form\WorkerValidationReminderType;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\RecordsActionsWorker;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class AdminConfigurationController extends Controller
{
    public function indexAction(PhraseaApplication $app, Request $request)
    {
        return $this->render('admin/worker-manager/index.html.twig', [
            'isConnected'       => $this->getAMQPConnection()->getChannel() != null,
            '_fragment' => $request->get('_fragment') ?? 'worker-configuration',
        ]);
    }

    /**
     * @param PhraseaApplication $app
     * @param Request $request
     * @return mixed
     */
    public function configurationAction(PhraseaApplication $app, Request $request)
    {
        $AMQPConnection = $this->getAMQPConnection();

        $conf =  ['queues' => $this->getConf()->get(['workers', 'queues'], [])];
        // ttl's are saved in conf in ms, display in form as sec.
        foreach($conf['queues'] as $qname => $settings) {
            foreach ($settings as $k=>$v) {
                if(in_array($k, [AMQPConnection::TTL_RETRY, AMQPConnection::TTL_DELAYED])) {
                    $conf['queues'][$qname][$k] /= 1000.0;
                }
            }
        }

        $form = $app->form(new WorkerConfigurationType($AMQPConnection), $conf);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // save config
            // too bad we must remove null entries from data to not save in conf
            $_data = $form->getData();
            $data = $conf['queues'];    // we will save a patched conf (not only data) so custom settings will be preserved
            foreach($_data['queues'] as $qname => $settings) {
                $data[$qname] = [];
                foreach ($settings as $k=>$v) {
                    if(!is_null($v)) {     // ignore null values from form
                        if(in_array($k, [AMQPConnection::TTL_RETRY, AMQPConnection::TTL_DELAYED])) {
                            $v = (int)(1000 * (float)$v);
                        }
                        $data[$qname][$k] = $v;
                    }
                }
            }
            ksort($data);
            $app['conf']->set(['workers', 'queues'], $data);

            /*
             * todo : reinitialize q can't depend on form content :
             * e.g. if a ttl_retry is blank in form, the value should go back to default, so the q should be reinit.
             *
            $queues = array_intersect_key(AMQPConnection::$defaultQueues, $retryQueueConfig);
            $retryQueuesToReset = array_intersect_key(AMQPConnection::$defaultRetryQueues, array_flip($queues));

            // change the queue TTL
            $AMQPConnection->reinitializeQueue($retryQueuesToReset);
            $AMQPConnection->reinitializeQueue(AMQPConnection::$defaultDelayedQueues);
            */

            // too bad : _fragment does not work with our old url generator... it will be passed as plain url parameter
            return $app->redirectPath('worker_admin', ['_fragment'=>'worker-configuration']);
        }

        return $this->render('admin/worker-manager/worker_configuration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function infoAction(PhraseaApplication $app, Request $request)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];

        $reload = ($request->query->get('reload') == 1);
        $jobType = $request->query->get('jobType');
        $databoxId = empty($request->query->get('databoxId')) ? null : $request->query->get('databoxId');
        $recordId = empty($request->query->get('recordId')) ? null : $request->query->get('recordId');
        $timeFilter = empty($request->query->get('timeFilter')) ? null : $request->query->get('timeFilter');
        $fieldTimeFilter = $request->query->get('fieldTimeFilter');
        $fieldTimeFilter = $fieldTimeFilter?: 'created';

        $dateTimeFilter = null;
        if ($timeFilter != null) {
            try {
                $dateTimeFilter = (new \DateTime())->sub(new \DateInterval($timeFilter));
            } catch (\Exception $e) {
            }
        }

        $filterStatus = [];

        if ($request->query->get('running') == 1) {
            $filterStatus[] = WorkerRunningJob::RUNNING;
        }
        if ($request->query->get('finished') == 1) {
            $filterStatus[] = WorkerRunningJob::FINISHED;
        }
        if ($request->query->get('error') == 1) {
            $filterStatus[] = WorkerRunningJob::ERROR;
        }
        if ($request->query->get('interrupt') == 1) {
            $filterStatus[] = WorkerRunningJob::INTERRUPT;
        }

        $helpers = new PhraseanetExtension($this->app);

        $workerRunningJob = $repoWorker->findByFilter($filterStatus, $jobType, $databoxId, $recordId, $fieldTimeFilter, $dateTimeFilter);
        $workerRunningJobTotalCount = $repoWorker->getJobCount($filterStatus, $jobType, $databoxId, $recordId);
        $workerRunningJobTotalCount = number_format($workerRunningJobTotalCount, 0, '.', ' ');
        $totalDuration = array_sum(array_column($workerRunningJob, 'duration'));
        // format duration
        $totalDuration  = $helpers->getDuration($totalDuration);

        $tFieldTimes = array_column($workerRunningJob, $fieldTimeFilter);
        $realEntryDuration = 0;
        $oldestEntry = end($tFieldTimes);
        $recentEntry = reset($tFieldTimes);

        if (!empty($oldestEntry) && !empty($recentEntry)) {
            $realEntryDuration = (new \DateTime($recentEntry))->getTimestamp() - (new \DateTime($oldestEntry))->getTimestamp();
        }

        $realEntryDuration = $helpers->getDuration($realEntryDuration);

        // get all row count in the table WorkerRunningJob
        $totalCount = $repoWorker->getJobCount([], null, null , null);
        $totalCount = number_format($totalCount, 0, '.', ' ');

        $databoxIds = array_map(function (\databox $databox) {
                return $databox->get_sbas_id();
            },
            $this->app->getApplicationBox()->get_databoxes()
        );

        $types = AMQPConnection::MESSAGES;

        // these types are not included in workerRunningJob
        unset($types['mainQueue'], $types['createRecord'], $types['pullAssets'], $types['validationReminder']);

        $jobTypes = array_keys($types);

        if ($reload) {
            return $this->app->json(['content' => $this->render('admin/worker-manager/worker_info.html.twig', [
                'workerRunningJob' => $workerRunningJob,
                'reload'           => $reload,
                'jobTypes'         => $jobTypes,
                'databoxIds'       => $databoxIds,
            ]),
                'resultCount'      => number_format(count($workerRunningJob), 0, '.', ' '),
                'resultTotal'      => $workerRunningJobTotalCount,
                'totalCount'       => $totalCount,
                'totalDuration'    => $totalDuration,
                'realEntryDuration'=> $realEntryDuration
            ]);
        } else {
            return $this->render('admin/worker-manager/worker_info.html.twig', [
                'workerRunningJob' => $workerRunningJob,
                'reload'           => $reload,
                'jobTypes'         => $jobTypes,
                'databoxIds'       => $databoxIds,
                'resultCount'      => number_format(count($workerRunningJob), 0, '.', ' '),
                'resultTotal'      => $workerRunningJobTotalCount,
                'totalCount'       => $totalCount,
                'totalDuration'    => $totalDuration,
                'realEntryDuration'=> $realEntryDuration
            ]);
        }
    }

    /**
     * @param Request $request
     * @param $workerId
     * @return JsonResponse
     * @throws OptimisticLockException
     */
    public function changeStatusAction(Request $request, $workerId)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $this->app['repo.worker-running-job'];

        /** @var WorkerRunningJob $workerRunningJob */
        $workerRunningJob = $repoWorker->find($workerId);

        $workerRunningJob->setStatus($request->request->get('status'));
        if($request->request->get('finished') == '1') {
            $workerRunningJob->setFinished(new \DateTime('now'))->setFlock(null);
        }

        $em = $repoWorker->getEntityManager();
        $em->persist($workerRunningJob);

        $em->flush();

        return $this->app->json(['success' => true]);
    }

    public function changeStatusCanceledAction(PhraseaApplication $app, Request $request)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $this->app['repo.worker-running-job'];

        $result = $repoWorker->getRunningSinceCreated($request->get('hour'));
        return $this->render('admin/worker-manager/worker_info_change_status.html.twig', [
            'jobCount' => count($result)
        ]);
    }

    public function doChangeStatusToCanceledAction(PhraseaApplication $app, Request $request)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $this->app['repo.worker-running-job'];
        $repoWorker->updateStatusRunningToCanceledSinceCreated($request->request->get('hour'));

        return $this->app->json(['success' => true]);
    }

    public function getRunningAction(PhraseaApplication $app, Request $request)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $this->app['repo.worker-running-job'];
        $result = $repoWorker->getRunningSinceCreated($request->get('hour'));

        return $this->app->json([
            'success'   => true,
            'count'     => count($result)
        ]);
    }

    public function queueMonitorAction(PhraseaApplication $app, Request $request)
    {
        $reload = ($request->query->get('reload') == 1);
        $hideEmptyQ = $request->query->get('hide-empty-queue');

        if ($hideEmptyQ === null || $hideEmptyQ == 1) {
            $hideEmptyQ = true;
        } else {
            $hideEmptyQ = false;
        }

        $this->getAMQPConnection()->getChannel();
        $this->getAMQPConnection()->declareExchange();
        $queuesStatus = $this->getAMQPConnection()->getQueuesStatus($hideEmptyQ);

        return $this->render('admin/worker-manager/worker_queue_monitor.html.twig', [
            'queuesStatus' => $queuesStatus,
            'reload'       => $reload
        ]);
    }

    public function purgeQueueAction(PhraseaApplication $app, Request $request)
    {
        $queueName = $request->request->get('queueName');

        if (empty($queueName)) {
            return $this->app->json(['success' => false]);
        }

        $this->getAMQPConnection()->reinitializeQueue([$queueName]);

        return $this->app->json(['success' => true]);
    }

    public function deleteQueueAction(PhraseaApplication $app, Request $request)
    {
        $queueName = $request->request->get('queueName');

        if (empty($queueName)) {
            return $this->app->json(['success' => false]);
        }

        $this->getAMQPConnection()->deleteQueue($queueName);

        return $this->app->json(['success' => true]);
    }

    public function truncateTableAction(PhraseaApplication $app)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];
        $repoWorker->truncateWorkerTable();

        // too bad : _fragment does not work with our old url generator... it will be passed as plain url parameter
        return $app->redirectPath('worker_admin', ['_fragment'=>'worker-info']);
    }

    public function deleteFinishedAction(PhraseaApplication $app)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];
        $repoWorker->deleteFinishedWorks();

        // too bad : _fragment does not work with our old url generator... it will be passed as plain url parameter
        return $app->redirectPath('worker_admin', ['_fragment'=>'worker-info']);
    }

    public function searchengineAction(PhraseaApplication $app, Request $request)
    {
        $options = $this->getElasticsearchOptions();

        $form = $app->form(new WorkerSearchengineType(), $options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $populateInfo = $this->getData($form);

            $this->getDispatcher()->dispatch(WorkerEvents::POPULATE_INDEX, new PopulateIndexEvent($populateInfo));

            // too bad : _fragment does not work with our old url generator... it will be passed as plain url parameter
            return $app->redirectPath('worker_admin', ['_fragment'=>'worker-searchengine']);
        }

        return $this->render('admin/worker-manager/worker_searchengine.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function subviewAction()
    {
        return $this->render('admin/worker-manager/worker_subview.html.twig', [ ]);
    }

    public function metadataAction()
    {
        return $this->render('admin/worker-manager/worker_metadata.html.twig', [ ]);
    }

    public function ftpAction(PhraseaApplication $app, Request $request)
    {
        $ftpConfig = $this->getFtpConfiguration();
        $form = $app->form(new WorkerFtpType(), $ftpConfig);

        $form->handleRequest($request);
        if ($form->isValid()) {
            // save new ftp config
            $app['conf']->set(['workers', 'ftp'], array_merge($ftpConfig, $form->getData()));

            // too bad : _fragment does not work with our old url generator... it will be passed as plain url parameter
            return $app->redirectPath('worker_admin', ['_fragment'=>'worker-ftp']);
        }

        return $this->render('admin/worker-manager/worker_ftp.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function validationReminderAction(PhraseaApplication $app, Request $request)
    {
        // nb : the "interval" for a loop-q is the ttl.
        // so the setting is stored into the "queues" settings in conf.
        // here only the "ttl_retry" can be set/changed in conf
        $config = $this->getConf()->get(['workers', 'queues', MessagePublisher::VALIDATION_REMINDER_TYPE], []);
        if(isset($config['ttl_retry'])) {
            // all settings are in msec, but into the form we want large numbers in sec.
            $config['ttl_retry'] /= 1000;
        }
        /** @var Form $form */
        $form = $app->form(new WorkerValidationReminderType($this->getAMQPConnection()), $config);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            switch($data['act']) {
                case 'save' :   // save the form content (settings)
                    unset($data['act']);    // don't save this
                    // the interval was displayed in sec. in form, convert back to msec
                    if(isset($data['ttl_retry'])) {
                        $data['ttl_retry'] *= 1000;
                    }
                    $data = array_merge($config, $data);
                    $app['conf']->set(['workers', 'queues', MessagePublisher::VALIDATION_REMINDER_TYPE], $data);
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::VALIDATION_REMINDER_TYPE]);
                    break;
                case 'start':
                    // reinitialize the validation reminder queues
                    $this->getAMQPConnection()->setQueue(MessagePublisher::VALIDATION_REMINDER_TYPE);
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::VALIDATION_REMINDER_TYPE]);
                    $this->getMessagePublisher()->initializeLoopQueue(MessagePublisher::VALIDATION_REMINDER_TYPE);
                    break;
                case 'stop':
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::VALIDATION_REMINDER_TYPE]);
                    break;
            }

            // too bad : _fragment does not work with our old url generator... it will be passed as plain url parameter
            return $app->redirectPath('worker_admin', ['_fragment'=>'worker-reminder']);
        }

        // guess if the q is "running" = check if there are pending message on Q or loop-Q
        $running = false;
        $qStatuses = $this->getAMQPConnection()->getQueuesStatus();
        foreach([
                    MessagePublisher::VALIDATION_REMINDER_TYPE,
                    $this->getAMQPConnection()->getLoopQueueName(MessagePublisher::VALIDATION_REMINDER_TYPE)
                ] as $qName) {
            if(isset($qStatuses[$qName]) && $qStatuses[$qName]['messageCount'] > 0) {
                $running = true;
            }
        }

        return $this->render('admin/worker-manager/worker_validation_reminder.html.twig', [
            'form' => $form->createView(),
            'running' => $running
        ]);
    }

    public function recordsActionsAction(PhraseaApplication $app, Request $request)
    {
        $config = $this->getConf()->get(['workers', 'records_actions'], []);
        $ttl_retry = $this->getConf()->get(['workers','queues', MessagePublisher::RECORDS_ACTIONS_TYPE, 'ttl_retry'], null);
        if(!is_null($ttl_retry)) {
            $ttl_retry /= 1000;     // form is in sec
        }
        $config['ttl_retry'] = $ttl_retry;

        if (empty($config['xmlSetting'])) {
            $config['xmlSetting'] = $this->getDefaultRecordsActionsSettings();
        }

        $form = $app->form(new WorkerRecordsActionsType(), $config);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            switch($data['act']) {
                case 'save' :   // save the form content (settings) in 2 places
                    $ttl_retry = $data['ttl_retry'];
                    unset($data['act'], $data['ttl_retry'], $config['ttl_retry']);
                    // save most data under workers/records_actions
                    $app['conf']->set(['workers', 'records_actions'], array_merge($config, $data));
                    // save ttl in the q settings
                    if(!is_null($ttl_retry)) {
                        $this->getConf()->set(['workers','queues', MessagePublisher::RECORDS_ACTIONS_TYPE, 'ttl_retry'], 1000 * (int)$ttl_retry);
                    }
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::RECORDS_ACTIONS_TYPE]);
                    break;
                case 'start':
                    // reinitialize the validation reminder queues
                    $this->getAMQPConnection()->setQueue(MessagePublisher::RECORDS_ACTIONS_TYPE);
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::RECORDS_ACTIONS_TYPE]);
                    $this->getMessagePublisher()->initializeLoopQueue(MessagePublisher::RECORDS_ACTIONS_TYPE);
                    break;
                case 'stop':
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::RECORDS_ACTIONS_TYPE]);
                    break;
            }

            return $app->redirectPath('worker_admin', ['_fragment'=>'worker-records-actions']);
        }

        // guess if the q is "running" = check if there are pending message on Q or loop-Q
        $running = false;
        $qStatuses = $this->getAMQPConnection()->getQueuesStatus();
        foreach([
                    MessagePublisher::RECORDS_ACTIONS_TYPE,
                    $this->getAMQPConnection()->getLoopQueueName(MessagePublisher::RECORDS_ACTIONS_TYPE)
                ] as $qName) {
            if(isset($qStatuses[$qName]) && $qStatuses[$qName]['messageCount'] > 0) {
                $running = true;
            }
        }

        return $this->render('admin/worker-manager/worker_records_actions.html.twig', [
            'form'      => $form->createView(),
            'running'   => $running
        ]);
    }

    public function recordsActionsFacilityAction(PhraseaApplication $app, Request $request)
    {
        $ret = ['tasks' => []];
        $job = new RecordsActionsWorker($app);
        switch ($request->get('ACT')) {
            case 'PLAYTEST':
                $sxml = simplexml_load_string($request->get('xml'));
                if (isset($sxml->tasks->task)) {
                    foreach ($sxml->tasks->task as $sxtask) {
                        $ret['tasks'][] = $job->calcSQL($app, $sxtask, true);
                    }
                }
                break;
            case 'CALCTEST':
            case 'CALCSQL':
                $sxml = simplexml_load_string($request->get('xml'));
                if (isset($sxml->tasks->task)) {
                    foreach ($sxml->tasks->task as $sxtask) {
                        $ret['tasks'][] = $job->calcSQL($app, $sxtask, false);
                    }
                }
                break;
            default:
                throw new NotFoundHttpException('Route not found.');
        }

        return $app->json($ret);
    }

    public function populateStatusAction(PhraseaApplication $app, Request $request)
    {
        $databoxIds = $request->get('sbasIds');

        /** @var WorkerRunningJobRepository $repoWorkerJob */
        $repoWorkerJob = $app['repo.worker-running-job'];

        return $repoWorkerJob->checkPopulateStatusByDataboxIds($databoxIds);
    }

    private function getDefaultRecordsActionsSettings()
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <!--
        THIS IS AN EXAMPLE OF A SIMPLE WORKFLOW
        Fix with your settings (fields names, databox/collections id's, status-bits) before try
    -->
    <tasks>

        <comment> keep offline (sb4 = 1) all docs before their "go online" date and after credate (record column) </comment>

        <task active="0" name="stay offline" action="update" databoxId="1">
            <from>
                <date direction="before" field="GO_ONLINE"/>
                <date direction="after" field="#credate" />
            </from>
            <to>
                <status mask="x1xxxx"/>
            </to>
        </task>


        <comment> Put online (sb4 = 0) all docs from 'public' collection and between the online date and the date of archiving </comment>

        <task active="0" name="go online" action="update" databoxId="1">
            <from>
                <comment> 5, 6, 7 are "public" collections </comment>
                <coll compare="=" id="5,6,7"/>
                <date direction="after" field="GO_ONLINE"/>
                <date direction="before" field="TO_ARCHIVE"/>
            </from>
            <to>
                <status mask="x0xxxx"/>
            </to>
        </task>


        <comment> Warn 10 days before archiving (raise sb5) </comment>

        <task active="0" name="almost the end" action="update" databoxId="1">
            <from>
                <coll compare="=" id="5,6,7"/>
                <date direction="after" field="TO_ARCHIVE" delta="-10"/>
            </from>
            <to>
                <status mask="1xxxxx"/>
            </to>
        </task>


        <comment> Move to 'archive' collection </comment>

        <task active="0" name="archivage" action="update" databoxId="1">
            <from>
                <coll compare="=" id="5,6,7"/>
                <date direction="after" field="TO_ARCHIVE" />
            </from>
            <to>
                <comment> reset status of archived documents </comment>
                <status mask="00xxxx"/>
                <comment> 666 is the "archive" collection </comment>
                <coll id="666" />
            </to>
        </task>


        <comment> Delete the documents that are in the trash collection unmodified from 3 months </comment>

        <task active="0" name="trash" action="delete" databoxId="1">
            <from>
                <coll compare="=" id="666"/>
                <date direction="after" field="#moddate" delta="+90" />
            </from>
        </task>
    </tasks>

</tasksettings>
EOF;
    }


    /**
     * @return MessagePublisher
     */
    private function getMessagePublisher()
    {
        return $this->app['alchemy_worker.message.publisher'];
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getDispatcher()
    {
        return $this->app['dispatcher'];
    }

    /**
     * @return ElasticsearchOptions
     */
    private function getElasticsearchOptions()
    {
        return $this->app['elasticsearch.options'];
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    private function getData(FormInterface $form)
    {
        /** @var ElasticsearchOptions $options */
        $options = $form->getData();

        $data['host'] = $options->getHost();
        $data['port'] = $options->getPort();
        $data['indexName'] = $options->getIndexName();
        $data['databoxIds'] = $form->getExtraData()['sbas'];

        return $data;
    }

    private function getFtpConfiguration()
    {
        return $this->getConf()->get(['workers', 'ftp'], []);
    }

    /**
     * @return AMQPConnection
     */
    private function getAMQPConnection()
    {
        return $this->app['alchemy_worker.amqp.connection'];
    }

    /**
     * @return UrlGeneratorInterface
     */
    private function getUrlGenerator()
    {
        return $this->app['url_generator'];
    }

}
