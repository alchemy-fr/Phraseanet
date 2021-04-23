<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;

class ExposeUploadWorker implements WorkerInterface
{
    use ApplicationBoxAware;

    /** @var  WorkerRunningJobRepository $repoWorker*/
    private $repoWorker;
    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    private $app;

    public function __construct($app)
    {
        $this->app              = $app;
        $this->repoWorker       = $app['repo.worker-running-job'];
        $this->messagePublisher = $app['alchemy_worker.message.publisher'];
    }

    public function process(array $payload)
    {
        $em = $this->repoWorker->getEntityManager();

        $message = [
            'message_type'  => MessagePublisher::EXPOSE_UPLOAD_TYPE,
            'payload'       => $payload
        ];

        if (isset($payload['workerJobId'])) {
            /** @var WorkerRunningJob $workerRunningJob */
            $workerRunningJob = $this->repoWorker->find($payload['workerJobId']);

            if ($workerRunningJob == null) {
                $this->messagePublisher->pushLog("Given workerJobId not found !", 'error');

                return ;
            }

            $workerRunningJob
                ->setInfo(WorkerRunningJob::ATTEMPT . $payload['count'])
                ->setStatus(WorkerRunningJob::RUNNING);

            $em->persist($workerRunningJob);

            $em->flush();

        } else {
            $em->beginTransaction();
            $date = new \DateTime();

            $workerRunningJob = new WorkerRunningJob();

            try {
                $workerRunningJob
                    ->setWork(MessagePublisher::EXPOSE_UPLOAD_TYPE)
                    ->setDataboxId($payload['databoxId'])
                    ->setRecordId($payload['recordId'])
                    ->setWorkOn($payload['exposeName'])
                    ->setPayload($message)
                    ->setPublished($date->setTimestamp($payload['published']))
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                $em->flush();

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        }

        try {
            $exposeConfiguration = $this->app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
            $exposeConfiguration = $exposeConfiguration[$payload['exposeName']];

            $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

            $record = $this->findDataboxById($payload['databoxId'])->get_record($payload['recordId']);

            $helpers = new PhraseanetExtension($this->app);
            $canSeeBusiness = $helpers->isGrantedOnCollection($record->getBaseId(), [\ACL::CANMODIFRECORD]);

            $captionsByfield = $record->getCaption($helpers->getCaptionFieldOrder($record, $canSeeBusiness));

            $description = "<dl>";

            foreach ($captionsByfield as $name => $value) {
                if ($helpers->getCaptionFieldGuiVisible($record, $name) == 1) {
                    $description .= "<dt>" . $helpers->getCaptionFieldLabel($record, $name). "</dt>";
                    $description .= "<dd>" . $helpers->getCaptionField($record, $name, $value). "</dd>";
                }
            }

            $description .= "</dl>";

            $databox = $record->getDatabox();
            $caption = $record->get_caption();
            $lat = $lng = null;

            foreach ($databox->get_meta_structure() as $meta) {
                if (strpos(strtolower($meta->get_name()), 'longitude') !== FALSE  && $caption->has_field($meta->get_name())) {
                    // retrieve value for the corresponding field
                    $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                    $fieldValue = array_pop($fieldValues);
                    $lng = $fieldValue->getValue();

                } elseif (strpos(strtolower($meta->get_name()), 'latitude') !== FALSE  && $caption->has_field($meta->get_name())) {
                    // retrieve value for the corresponding field
                    $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                    $fieldValue = array_pop($fieldValues);
                    $lat = $fieldValue->getValue();

                }
            }

            $instanceKey = $this->app['conf']->get(['main', 'key']);

            // this is the unique reference for record in phraseanet and assets in expose
            // instanceKey_basedID_record_id
            $assetId = $instanceKey.'_'.$record->getId();

            $requestBody = [
                'publication_id' => $payload['publicationId'],
                'description'    => $description,
                'asset_id'       => $assetId,
                'upload' => [
                    'type' => $record->getMimeType(),
                    'size' => $record->getSize(),
                    'name' => $record->getOriginalName()

                ]
            ];

            if ($lat !== null) {
                $requestBody['lat'] = $lat;
            }

            if ($lng !== null) {
                $requestBody['lng'] = $lng;
            }

            $response = $exposeClient->post('/assets', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $payload['accessToken']
                ],
                'json' => $requestBody
            ]);

            if ($response->getStatusCode() !==201) {
                $this->messagePublisher->pushLog("An error occurred when creating asset: status-code " . $response->getStatusCode());
                $this->finishedJob($workerRunningJob, $em, WorkerRunningJob::ERROR);

                return ;
            }

            $assetsResponse = json_decode($response->getBody(),true);

            $uploadUrl = new Client();
            $uploadUrl->put($assetsResponse['uploadURL'], [
                'headers' => [
                    'Content-Type' => 'application/binary'
                ],
                'body' => fopen($record->get_subdef('document')->getRealPath(), 'r')
            ]);

            // add preview sub-definition

            $this->postSubDefinition(
                $exposeClient,
                $payload['accessToken'],
                $assetsResponse['id'],
                $record->get_subdef('preview'),
                'preview',
                true
            );

            // add thumbnail sub-definition

            $this->postSubDefinition(
                $exposeClient,
                $payload['accessToken'],
                $assetsResponse['id'],
                $record->get_subdef('thumbnail'),
                'thumbnail',
                false,
                true
            );

            $this->messagePublisher->pushLog("Asset ID :". $assetsResponse['id'] ." successfully uploaded! ");
        } catch (\Exception $e) {
            $workerMessage = "An error occurred when creating asset!: ". $e->getMessage();

            $this->messagePublisher->pushLog($workerMessage);

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            $this->repoWorker->reconnect();
            $em->beginTransaction();
            try {
                $workerRunningJob
                    ->setInfo(WorkerRunningJob::ATTEMPT. ($count - 1))
                    ->setStatus(WorkerRunningJob::ERROR)
                ;

                $em->persist($workerRunningJob);
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }

            $payload['workerJobId'] = $workerRunningJob->getId();
            $fullPayload = [
                'message_type'  => MessagePublisher::EXPOSE_UPLOAD_TYPE,
                'payload'       => $payload
            ];

            $this->messagePublisher->publishRetryMessage(
                $fullPayload,
                MessagePublisher::EXPOSE_UPLOAD_TYPE,
                $count,
                $workerMessage
            );

            return;
        }

        // tell that the upload is finished
        $this->finishedJob($workerRunningJob, $em);
    }

    private function postSubDefinition(Client $exposeClient, $token, $assetId, \media_subdef $subdef, $subdefName, $isPreview = false, $isThumbnail = false)
    {
        $requestBody = [
            'asset_id' => $assetId,
            'name'     => $subdefName,
            'use_as_preview'    => $isPreview,
            'use_as_thumbnail'  => $isThumbnail,
            'upload' => [
                'type' => $subdef->get_mime(),
                'size' => $subdef->get_size(),
                'name' => $subdef->get_file()

            ]
        ];

        $response = $exposeClient->post('/sub-definitions', [
            'headers' => [
                'Authorization' => 'Bearer ' .$token
            ],
            'json'  => $requestBody
        ]);

        if ($response->getStatusCode() !==201) {
            $this->messagePublisher->pushLog("An error occurred when adding sub-definition: status-code " . $response->getStatusCode());

            return ;
        }

        $subDefResponse = json_decode($response->getBody(),true);

        $uploadUrl = new Client();
        $res = $uploadUrl->put($subDefResponse['uploadURL'], [
            'headers' => [
                'Content-Type' => 'application/binary'
            ],
            'body' => fopen($subdef->getRealPath(), 'r')
        ]);

        if ($res->getStatusCode() !==200) {
            $this->messagePublisher->pushLog("An error occurred when sending file, status-code : " . $response->getStatusCode());

            return ;
        }
    }

    private function finishedJob(WorkerRunningJob $workerRunningJob, EntityManager $em, $status = WorkerRunningJob::FINISHED)
    {
        $this->repoWorker->reconnect();
        $em->getConnection()->beginTransaction();
        try {
            $workerRunningJob->setStatus($status);
            $workerRunningJob->setFinished(new \DateTime('now'));
            $em->persist($workerRunningJob);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $this->messagePublisher->pushLog("Error when wanting to update database :" . $e->getMessage());
            $em->rollback();
        }
    }
}
