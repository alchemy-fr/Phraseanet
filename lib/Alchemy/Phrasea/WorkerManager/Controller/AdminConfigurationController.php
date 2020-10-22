<?php

namespace Alchemy\Phrasea\WorkerManager\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Form\WorkerConfigurationType;
use Alchemy\Phrasea\WorkerManager\Form\WorkerPullAssetsType;
use Alchemy\Phrasea\WorkerManager\Form\WorkerSearchengineType;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminConfigurationController extends Controller
{
    public function indexAction(PhraseaApplication $app)
    {
        /** @var AMQPConnection $serverConnection */
        $serverConnection = $this->app['alchemy_worker.amqp.connection'];

        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];

        return $this->render('admin/worker-manager/index.html.twig', [
            'isConnected'       => ($serverConnection->getChannel() != null) ? true : false,
            'workerRunningJob'  => $repoWorker->findAll(),
        ]);
    }

    /**
     * @param PhraseaApplication $app
     * @param Request $request
     * @return mixed
     */
    public function configurationAction(PhraseaApplication $app, Request $request)
    {
        $retryQueueConfig = $this->getRetryQueueConfiguration();

        $form = $app->form(new WorkerConfigurationType(), $retryQueueConfig);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // save config in file
            $app['conf']->set(['workers', 'retry_queue'], $form->getData());

            $queues = array_intersect_key(AMQPConnection::$defaultQueues, $retryQueueConfig);
            $retryQueuesToReset = array_intersect_key(AMQPConnection::$defaultRetryQueues, array_flip($queues));

            /** @var AMQPConnection $serverConnection */
            $serverConnection = $this->app['alchemy_worker.amqp.connection'];
            // change the queue TTL
            $serverConnection->reinitializeQueue($retryQueuesToReset);
            $serverConnection->reinitializeQueue(AMQPConnection::$defaultDelayedQueues);

            return $app->redirectPath('worker_admin');
        }

        return $this->render('admin/worker-manager/worker_configuration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function infoAction(PhraseaApplication $app, Request $request)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];

        $reload = ($request->query->get('reload')) == 1 ? true : false ;

        $workerRunningJob = [];
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

        if (count($filterStatus) > 0) {
            $workerRunningJob = $repoWorker->findByStatus($filterStatus);
        }

        return $this->render('admin/worker-manager/worker_info.html.twig', [
            'workerRunningJob' => $workerRunningJob,
            'reload'           => $reload
        ]);
    }

    /**
     * @param Request $request
     * @param $workerId
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function changeStatusAction(Request $request, $workerId)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $this->app['repo.worker-running-job'];

        /** @var WorkerRunningJob $workerRunningJob */
        $workerRunningJob = $repoWorker->find($workerId);

        $workerRunningJob
            ->setStatus($request->request->get('status'))
            ->setFinished(new \DateTime('now'))
        ;

        $em = $repoWorker->getEntityManager();
        $em->persist($workerRunningJob);

        $em->flush();

        return $this->app->json(['success' => true]);
    }

    public function queueMonitorAction(PhraseaApplication $app, Request $request)
    {
        $reload = ($request->query->get('reload')) == 1 ? true : false ;

        /** @var  AMQPConnection $serverConnection */
        $serverConnection = $app['alchemy_worker.amqp.connection'];
        $serverConnection->getChannel();
        $serverConnection->declareExchange();
        $queuesStatus = $serverConnection->getQueuesStatus();

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

        /** @var AMQPConnection $serverConnection */
        $serverConnection = $this->app['alchemy_worker.amqp.connection'];

        $serverConnection->reinitializeQueue([$queueName]);

        return $this->app->json(['success' => true]);
    }

    public function truncateTableAction(PhraseaApplication $app)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];
        $repoWorker->truncateWorkerTable();

        return $app->redirectPath('worker_admin');
    }

    public function deleteFinishedAction(PhraseaApplication $app)
    {
        /** @var WorkerRunningJobRepository $repoWorker */
        $repoWorker = $app['repo.worker-running-job'];
        $repoWorker->deleteFinishedWorks();

        return $app->redirectPath('worker_admin');
    }

    public function searchengineAction(PhraseaApplication $app, Request $request)
    {
        $options = $this->getElasticsearchOptions();

        $form = $app->form(new WorkerSearchengineType(), $options);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $populateInfo = $this->getData($form);

            $this->getDispatcher()->dispatch(WorkerEvents::POPULATE_INDEX, new PopulateIndexEvent($populateInfo));

            return $app->redirectPath('worker_admin');
        }

        return $this->render('admin/worker-manager/worker_searchengine.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function subviewAction()
    {
        return $this->render('admin/worker-manager/worker_subview.html.twig', [
        ]);
    }

    public function metadataAction()
    {
        return $this->render('admin/worker-manager/worker_metadata.html.twig', [
        ]);
    }

    public function populateStatusAction(PhraseaApplication $app, Request $request)
    {
        $databoxIds = $request->get('sbasIds');

        /** @var WorkerRunningJobRepository $repoWorkerJob */
        $repoWorkerJob = $app['repo.worker-running-job'];

        return $repoWorkerJob->checkPopulateStatusByDataboxIds($databoxIds);
    }

    public function pullAssetsAction(PhraseaApplication $app, Request $request)
    {
        $pullAssetsConfig = $this->getPullAssetsConfiguration();
        $form = $app->form(new WorkerPullAssetsType(), $pullAssetsConfig);

        $form->handleRequest($request);
        if ($form->isValid()) {
            /** @var AMQPConnection $serverConnection */
            $serverConnection = $this->app['alchemy_worker.amqp.connection'];
            $serverConnection->setQueue(MessagePublisher::PULL_QUEUE);

            // save new pull config
            $app['conf']->set(['workers', 'pull_assets'], array_merge($pullAssetsConfig, $form->getData()));

            // reinitialize the pull queues
            $serverConnection->reinitializeQueue([MessagePublisher::PULL_QUEUE]);
            $this->app['alchemy_worker.message.publisher']->initializePullAssets();

            return $app->redirectPath('worker_admin');
        }

        return $this->render('admin/worker-manager/worker_pull_assets.html.twig', [
            'form' => $form->createView()
        ]);
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

    private function getPullAssetsConfiguration()
    {
        return $this->app['conf']->get(['workers', 'pull_assets'], []);
    }

    private function getRetryQueueConfiguration()
    {
        return $this->app['conf']->get(['workers', 'retry_queue'], []);
    }
}
