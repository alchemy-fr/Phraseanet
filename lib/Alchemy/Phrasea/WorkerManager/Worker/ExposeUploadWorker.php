<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
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
        $em->beginTransaction();
        $date = new \DateTime();

        $message = [
            'message_type'  => MessagePublisher::EXPOSE_UPLOAD_TYPE,
            'payload'       => $payload
        ];
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

        // TODO: taken account admin config ,access_token for user or client_credentiels

        $exposeConfiguration = $this->app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
        $exposeConfiguration = $exposeConfiguration[$payload['exposeName']];

        $exposeClient = new Client(['base_uri' => $exposeConfiguration['expose_base_uri'], 'http_errors' => false]);

        $record = $this->findDataboxById($payload['databoxId'])->get_record($payload['recordId']);

        try {
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

            $multipartData = [
                [
                    'name'      => 'file',
                    'contents'  => fopen($record->get_subdef('document')->getRealPath(), 'r')
                ],
                [
                    'name'      => 'publication_id',
                    'contents'  => $payload['publicationId'],

                ],
                [
                    'name'      => 'slug',
                    'contents'  => 'asset_'. $record->getId()
                ],
                [
                    'name'      => 'description',
                    'contents'  => $description
                ]
            ];

            if ($lat !== null) {
                array_push($multipartData, [
                    'name'      => 'lat',
                    'contents'  => $lat
                ]);
            }

            if ($lng !== null) {
                array_push($multipartData, [
                    'name'      => 'lng',
                    'contents'  => $lng
                ]);
            }

            $response = $exposeClient->post('/assets', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $exposeConfiguration['token']
                ],
                'multipart' => $multipartData
            ]);

            if ($response->getStatusCode() !==201) {
                $this->messagePublisher->pushLog("An error occurred when creating asset: status-code " . $response->getStatusCode());
            }

            $assetsResponse = json_decode($response->getBody(),true);

            // add preview sub-definition

            $this->postSubDefinition(
                $exposeClient,
                $exposeConfiguration['token'],
                $record->get_subdef('preview')->getRealPath(),
                $assetsResponse['id'],
                'preview',
                true
            );

            // add thumbnail sub-definition

            $this->postSubDefinition(
                $exposeClient,
                $exposeConfiguration['token'],
                $record->get_subdef('thumbnail')->getRealPath(),
                $assetsResponse['id'],
                'thumbnail',
                false,
                true
            );


        } catch (\Exception $e) {
            $this->messagePublisher->pushLog("An error occurred when creating asset!");
        }

        // tell that the upload is finished
        $this->repoWorker->reconnect();
        $em->getConnection()->beginTransaction();
        try {
            $workerRunningJob->setStatus(WorkerRunningJob::FINISHED);
            $workerRunningJob->setFinished(new \DateTime('now'));
            $em->persist($workerRunningJob);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $this->messagePublisher->pushLog("Error when wanting to update database :" . $e->getMessage());
            $em->rollback();
        }
    }

    private function postSubDefinition(Client $exposeClient, $token, $path, $assetId, $subdefName, $isPreview = false, $isThumbnail = false)
    {
        return $exposeClient->post('/sub-definitions', [
            'headers' => [
                'Authorization' => 'Bearer ' .$token
            ],
            'multipart' => [
                [
                    'name'      => 'file',
                    'contents'  => fopen($path, 'r')
                ],
                [
                    'name'      => 'asset_id',
                    'contents'  => $assetId,

                ],
                [
                    'name'      => 'name',
                    'contents'  => $subdefName
                ],
                [
                    'name'      => 'use_as_preview',
                    'contents'  => $isPreview
                ],
                [
                    'name'      => 'use_as_thumbnail',
                    'contents'  => $isThumbnail
                ]
            ]
        ]);
    }
}
