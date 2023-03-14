<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
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

            $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
            $clientOptions = [
                'base_uri'      => $exposeConfiguration['expose_base_uri'],
                'http_errors'   => false,
                'verify'        => $exposeConfiguration['verify_ssl']
            ];

            // add proxy in each request if defined in configuration
            $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

            $record = $this->findDataboxById($payload['databoxId'])->get_record($payload['recordId']);

            $helpers = new PhraseanetExtension($this->app);

            // the identification of phraseanet instance in expose
            $phraseanetLocalId = $this->app['conf']->get(['phraseanet-service', 'phraseanet_local_id']);

            // get mapping if exist
            $clientAnnotationProfile = $this->getClientAnnotationProfile($exposeClient, $payload['accessToken'], $payload['publicationId']);

            $exposeFieldMappingName = $phraseanetLocalId . '_field_mapping';
            $fieldMapping = !empty($clientAnnotationProfile[$exposeFieldMappingName]) ? $clientAnnotationProfile[$exposeFieldMappingName] : [];
            $fieldListToUpload = !empty($fieldMapping['fields']) ? $fieldMapping['fields'] : [];

            // if not have setting sendGeoloc, by default always send the geoloc
            $sendGeolocField = isset($fieldMapping['sendGeolocField']) ? $fieldMapping['sendGeolocField'] : [$payload['databoxId']];

            // if not have setting sendVttField, by default always send the vtt
            $sendVttField = isset($fieldMapping['sendVttField']) ? $fieldMapping['sendVttField'] : [$payload['databoxId']];

            $description = "<dl>";

            foreach ($fieldListToUpload as $value) {
                // value as databoxId_metaId
                $t = explode('_', $value);

                // check if it is on the same databox
                if ($payload['databoxId'] == $t[0]) {
                    $fieldName = $record->getDatabox()->get_meta_structure()->get_element($t[1])->get_name();
                    if ($record->get_caption()->has_field($fieldName) && $helpers->getCaptionFieldGuiVisible($record, $fieldName) == 1) {
                        // retrieve value for the corresponding field
                        $captionField =  $record->get_caption()->get_field($fieldName);
                        $fieldValues = $captionField->get_values();

                        $fieldType = $captionField->get_databox_field()->get_type();
                        $fieldLabel = $helpers->getCaptionFieldLabel($record, $fieldName);

                        $description .= "<dt class='field-title field-type-". $fieldType ." field-name-". $fieldLabel ."' >" . $fieldLabel. "</dt>";
                        $description .= "<dd class='field-value field-type-". $fieldType ." field-name-". $fieldLabel ."' >" . $helpers->getCaptionField($record, $fieldName, $fieldValues). "</dd>";
                    }
                }
            }

            $description .= "</dl>";

            $databox = $record->getDatabox();
            $caption = $record->get_caption();
            $lat = $lng = null;
            $webVTT = '';

            if (in_array($payload['databoxId'], $sendGeolocField)) {
                $latFieldName = $lonFieldName = '';
                foreach($this->app['conf']->get(['geocoding-providers'], []) as $provider) {
                    if ($provider['enabled'] && array_key_exists('position-fields', $provider)) {
                        foreach ($provider['position-fields'] as $position_field) {
                            switch ($position_field['type']) {
                                case 'lat':
                                    $latFieldName = $position_field['name'];
                                    break;
                                case 'lon':
                                    $lonFieldName = $position_field['name'];
                                    break;
                            }
                        }
                    }
                }

                if (!empty($lonFieldName) && !empty($latFieldName)) {
                    foreach ($databox->get_meta_structure() as $meta) {
                        if (strpos(strtolower($meta->get_name()), strtolower($lonFieldName)) !== FALSE  && $caption->has_field($meta->get_name())) {
                            // retrieve value for the corresponding field
                            $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                            $fieldValue = array_pop($fieldValues);
                            $lng = $fieldValue->getValue();

                        } elseif (strpos(strtolower($meta->get_name()), strtolower($latFieldName)) !== FALSE  && $caption->has_field($meta->get_name())) {
                            // retrieve value for the corresponding field
                            $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                            $fieldValue = array_pop($fieldValues);
                            $lat = $fieldValue->getValue();

                        }
                    }
                }
            }

            if (in_array($payload['databoxId'], $sendVttField)) {
                foreach ($databox->get_meta_structure() as $meta) {
                    if (strpos(strtolower($meta->get_name()), strtolower('VideoTextTrack')) !== FALSE  && $caption->has_field($meta->get_name())) {
                        // retrieve value for the corresponding field
                        $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                        $fieldValue = array_pop($fieldValues);

                        $webVTT .= "\n\n" .$fieldValue->getValue();
                    }
                }
            }

            $exposeSubdefMappingName = $phraseanetLocalId . '_subdef_mapping';
            $actualSubdefMapping = !empty($clientAnnotationProfile[$exposeSubdefMappingName]) ? $clientAnnotationProfile[$exposeSubdefMappingName] : [];
            $documentType = $record->getType();

            $mapping = [];
            if (!empty($actualSubdefMapping[$payload['databoxId']][$documentType])) {
                $mapping = $actualSubdefMapping[$payload['databoxId']][$documentType];
            }

            $phraseanetSubdefAsDocument = array_search('document', $mapping);

            // if there is no subdef mapping for the expose document , use the phraseanet document by default
            $phraseanetSubdefAsDocument = $phraseanetSubdefAsDocument ?: 'document';

            // do not upload this as subdefinition
            unset($mapping[$phraseanetSubdefAsDocument]);

            // this is the unique reference for record in phraseanet and assets in expose
            // phraseanetLocalKey_basedID_record_id
            $assetId = $phraseanetLocalId.'_'.$record->getId();

            if ($record->has_subdef($phraseanetSubdefAsDocument) && $record->get_subdef($phraseanetSubdefAsDocument)->is_physically_present()) {
                $requestBody = [
                    'publication_id' => $payload['publicationId'],
                    'description'    => $description,
                    'asset_id'       => $assetId,
                    'upload' => [
                        'type' => $record->get_subdef($phraseanetSubdefAsDocument)->get_mime(),
                        'size' => $record->get_subdef($phraseanetSubdefAsDocument)->get_size(),
                        'name' => $record->get_subdef($phraseanetSubdefAsDocument)->get_file()

                    ]
                ];
            } else {
                $this->messagePublisher->pushLog(sprintf("subdefinition %s or file as document mapping not found", $phraseanetSubdefAsDocument));
                $this->finishedJob($workerRunningJob, $em, WorkerRunningJob::ERROR);

                return ;
            }

            if ($lat !== null) {
                $requestBody['lat'] = $lat;
            }

            if ($lng !== null) {
                $requestBody['lng'] = $lng;
            }

            if ($webVTT !== '') {
                $requestBody['webVTT'] = $webVTT;
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

            $uploadUrl = $proxyConfig->getClientWithOptions(['verify' => $exposeConfiguration['verify_ssl']]);
            $uploadUrl->put($assetsResponse['uploadURL'], [
                'headers' => [
                    'Content-Type' => 'application/binary'
                ],
                'body' => fopen($record->get_subdef($phraseanetSubdefAsDocument)->getRealPath(), 'r')
            ]);

            if (count($mapping) == 0) {
                $mapping = [
                    'thumbnail'  => 'thumbnail',
                    'preview'    => 'preview'
                ];
            }

            // add sub-definition from mapping
            foreach ($mapping as $phraseanetSubdef => $exposeSubdef) {
                $isThumbnail = $isPreview= false;
                switch ($exposeSubdef) {
                    case 'thumbnail':
                        $subdefName = $exposeSubdef;
                        $isThumbnail = true;
                        break;
                    case 'preview':
                        $subdefName = $exposeSubdef;
                        $isPreview = true;
                        break;
                    case 'none':
                    default:
                        $subdefName = $phraseanetSubdef;
                        break;
                }

                if ($record->has_subdef($phraseanetSubdef) && $record->get_subdef($phraseanetSubdef)->is_physically_present()) {
                    $this->postSubDefinition(
                        $exposeClient,
                        $payload['accessToken'],
                        $assetsResponse['id'],
                        $record->get_subdef($phraseanetSubdef),
                        $subdefName,
                        $isPreview,
                        $isThumbnail

                    );
                } else {
                    $this->messagePublisher->pushLog(sprintf("subdefinition %s or file not found, skipped", $phraseanetSubdef));
                }
            }

            $this->messagePublisher->pushLog("Asset ID :". $assetsResponse['id'] ." successfully uploaded! ");
        } catch (\Exception $e) {
//            $workerMessage = "An error occurred when creating asset!: ". $e->getMessage();
//
//            $this->messagePublisher->pushLog($workerMessage);
//
//            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;
//
//            $this->repoWorker->reconnect();
//            $em->beginTransaction();
//            try {
//                $workerRunningJob
//                    ->setInfo(WorkerRunningJob::ATTEMPT. ($count - 1))
//                    ->setStatus(WorkerRunningJob::ERROR)
//                ;
//
//                $em->persist($workerRunningJob);
//                $em->flush();
//                $em->commit();
//            } catch (\Exception $e) {
//                $em->rollback();
//            }
//
//            $payload['workerJobId'] = $workerRunningJob->getId();
//            $fullPayload = [
//                'message_type'  => MessagePublisher::EXPOSE_UPLOAD_TYPE,
//                'payload'       => $payload
//            ];
//
//            $this->messagePublisher->publishRetryMessage(
//                $fullPayload,
//                MessagePublisher::EXPOSE_UPLOAD_TYPE,
//                $count,
//                $workerMessage
//            );

            $this->messagePublisher->pushLog("An error occurred when creating asset!: ". $e->getMessage());
            $this->finishedJob($workerRunningJob, $em, WorkerRunningJob::ERROR);

            return ;

            return;
        }

        // tell that the upload is finished
        $this->finishedJob($workerRunningJob, $em);
    }

    private function getClientAnnotationProfile(Client $exposeClient, $accessToken, $publicationId)
    {
        $resPublication = $exposeClient->get('/publications/'.$publicationId , [
            'headers' => [
                'Authorization' => 'Bearer '. $accessToken,
            ]
        ]);

        $clientAnnotationProfile = [];
        if ($resPublication->getStatusCode() == 200) {
            $clientAnnotationProfile = json_decode($resPublication->getBody()->getContents(),true);
            if (!empty($clientAnnotationProfile['profile']) && !empty($clientAnnotationProfile['profile']['clientAnnotations'])) {
                $clientAnnotationProfile = json_decode($clientAnnotationProfile['profile']['clientAnnotations'], true);
            }
        }

        return $clientAnnotationProfile;
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

        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);

        $verifySsl = $exposeClient->getConfig('verify') ? true : false;
        $uploadUrl = $proxyConfig->getClientWithOptions(['verify' => $verifySsl]);

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
