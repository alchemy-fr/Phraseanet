<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
use Alchemy\Phrasea\WorkerManager\Event\AssetsCreationFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class AssetsIngestWorker implements WorkerInterface
{
    use EntityManagerAware;

    private $app;

    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;

    public function __construct(PhraseaApplication $app)
    {
        $this->app              = $app;
        $this->messagePublisher = $this->app['alchemy_worker.message.publisher'];
    }

    public function process(array $payload)
    {
        $assets = $payload['assets'];
        $this->repoWorkerJob = $this->getWorkerRunningJobRepository();

        $this->saveAssetsList($payload['commit_id'], $assets, $payload['published'], $payload['type']);

        if ($payload['type'] === WorkerRunningJob::TYPE_PUSH) {
            // get verify_ssl from config
            $verifySsl = $this->app['conf']->get(['phraseanet-service', 'uploader-service', 'push_verify_ssl'], true);
        } elseif ($payload['type'] === WorkerRunningJob::TYPE_PULL) {
            // the verify_ssl  in payload when pull is also from the config in a specific target name
            // it is injected from the PullAssetsWorker
            $verifySsl = isset($payload['verify_ssl']) ? $payload['verify_ssl'] : true ;
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
        $clientOptions = [
            'base_uri'  => $payload['base_url'],
            'verify'    => $verifySsl
        ];

        $uploaderClient = $proxyConfig->getClientWithOptions($clientOptions);

        //get first asset informations to check if it's a story
        try {
            $body = $uploaderClient->get('assets/'.$assets[0], [
                'headers' => [
                    'Authorization' => 'AssetToken '.$payload['token']
                ]
            ])->getBody()->getContents();
        } catch(\Exception $e) {
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            $this->app['dispatcher']->dispatch(WorkerEvents::ASSETS_CREATION_FAILURE, new AssetsCreationFailureEvent(
                $payload,
                'Error when getting assets information !' . $e->getMessage(),
                $count
            ));

            return;
        }

        $body = json_decode($body,true);

        $storyId = null;

        if (!empty($body['formData']['is_story'])) {
            $storyId = $this->createStory($body);
        }

        $em = $this->repoWorkerJob->getEntityManager();

        foreach ($assets as $assetId) {
            $createRecordMessage['message_type'] = MessagePublisher::CREATE_RECORD_TYPE;
            $createRecordMessage['payload'] = [
                'asset'      => $assetId,
                'publisher'  => $payload['publisher'],
                'assetToken' => $payload['token'],
                'storyId'    => $storyId,
                'base_url'   => $payload['base_url'],
                'commit_id'  => $payload['commit_id'],
                'verify_ssl' => $verifySsl
            ];

            $this->messagePublisher->publishMessage($createRecordMessage, MessagePublisher::CREATE_RECORD_TYPE);

            /** @var WorkerRunningJob $workerRunningJob */
            $workerRunningJob =  $this->repoWorkerJob->findOneBy([
                'commitId' => $payload['commit_id'],
                'assetId'  => $assetId,
                'finished' => null
            ]);

            $workerRunningJob->setPayload($createRecordMessage);
            $em->persist($workerRunningJob);

            unset($workerRunningJob);
        }
        $em->flush();
    }

    private function createStory(array $body)
    {
        $storyId = null;

        $userRepository = $this->getUserRepository();
        $user = null;

        if (!empty($body['formData']['phraseanet_submiter_email'])) {
            $user = $userRepository->findByEmail($body['formData']['phraseanet_submiter_email']);
        }

        if ($user === null && !empty($body['formData']['phraseanet_user_submiter_id'])) {
            $user = $userRepository->find($body['formData']['phraseanet_user_submiter_id']);
        }

        if ($user !== null) {
            $base_id = $body['formData']['collection_destination'];

            $collection = \collection::getByBaseId($this->app, $base_id);

            $story = \record_adapter::createStory($this->app, $collection);
            $storyId = $story->getRecordId();

            $storyWZ = new StoryWZ();

            $storyWZ->setUser($user);
            $storyWZ->setRecord($story);

            $entityManager = $this->getEntityManager();
            $entityManager->persist($storyWZ);
            $entityManager->flush();
        }

        return $storyId;
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @return WorkerRunningJobRepository
     */
    private function getWorkerRunningJobRepository()
    {
        return $this->app['repo.worker-running-job'];
    }

    private function saveAssetsList($commitId, $assetsId, $published, $type)
    {
        $em = $this->repoWorkerJob->getEntityManager();
        $em->beginTransaction();
        $date = new \DateTime();

        try {
            foreach ($assetsId as $assetId) {
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setCommitId($commitId)
                    ->setAssetId($assetId)
                    ->setPublished($date->setTimestamp($published))
                    ->setWork(MessagePublisher::ASSETS_INGEST_TYPE)
                    ->setWorkOn($type)
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                unset($workerRunningJob);
            }

            $em->flush();

            $em->commit();
        } catch(\Exception $e) {
            $em->rollback();
        }
    }
}
