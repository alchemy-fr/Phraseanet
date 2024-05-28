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

    private $exposeConfiguration;
    private $accessTokenInfo;

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
            $this->accessTokenInfo = $payload['accessTokenInfo'];
            $this->exposeConfiguration = $this->app['conf']->get(['phraseanet-service', 'expose-service', 'exposes'], []);
            $this->exposeConfiguration = $this->exposeConfiguration[$payload['exposeName']];

            $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);
            $clientOptions = [
                'base_uri'      => $this->exposeConfiguration['expose_base_uri'],
                'http_errors'   => false,
                'verify'        => $this->exposeConfiguration['verify_ssl']
            ];

            // add proxy in each request if defined in configuration
            $exposeClient = $proxyConfig->getClientWithOptions($clientOptions);

            $record = $this->findDataboxById($payload['databoxId'])->get_record($payload['recordId']);

            $helpers = new PhraseanetExtension($this->app);

            // the identification of phraseanet instance in expose
            $instanceId = $this->app['conf']->get(['main', 'instance_id']);

            // get mapping if exist
            $clientAnnotationProfile = $this->getClientAnnotationProfile($exposeClient, $payload['publicationId']);

            $exposeFieldMappingName = $instanceId . '_field_mapping';
            $fieldMapping = !empty($clientAnnotationProfile[$exposeFieldMappingName]) ? $clientAnnotationProfile[$exposeFieldMappingName] : [];
            $fieldListToUpload = !empty($fieldMapping['fields']) ? $fieldMapping['fields'] : [];

            // if not have setting sendGeoloc, by default always send the geoloc
            $sendGeolocField = isset($fieldMapping['sendGeolocField']) ? $fieldMapping['sendGeolocField'] : [$payload['databoxId']];

            // if not have setting sendVttField, by default always send the vtt
            $sendVttField = isset($fieldMapping['sendVttField']) ? $fieldMapping['sendVttField'] : [$payload['databoxId']];

            $description = "<dl>";

            foreach ($fieldListToUpload as $key => $fieldLabel) {
                // key as databoxId_metaId
                $t = explode('_', $key);

                // check if it is on the same databox
                if ($payload['databoxId'] == $t[0]) {
                    $fieldName = $record->getDatabox()->get_meta_structure()->get_element($t[1])->get_name();
                    if ($record->get_caption()->has_field($fieldName) && $helpers->getCaptionFieldGuiVisible($record, $fieldName) == 1) {
                        // retrieve value for the corresponding field
                        $captionField =  $record->get_caption()->get_field($fieldName);
                        $fieldValues = $captionField->get_values();
                        $fieldType = $captionField->get_databox_field()->get_type();

                        $description .= "<dt class='field-title field-type-". $fieldType ." field-name-". $fieldName ."' >" . $fieldLabel. "</dt>";
                        $description .= "<dd class='field-value field-type-". $fieldType ." field-name-". $fieldName ."' >" . $helpers->getCaptionField($record, $fieldName, $fieldValues). "</dd>";
                    }
                }
            }

            $description .= "</dl>";

            $databox = $record->getDatabox();
            $caption = $record->get_caption();
            $lat = $lng = null;
            $webVTT = [];

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
                    if (1 === preg_match('#^VideoTextTrack([a-z]{2}(?:[-_]\w+)?)$#i', trim($meta->get_name()), $matches)  && $caption->has_field($meta->get_name())) {
                        // retrieve value for the corresponding field
                        $fieldValues = $record->get_caption()->get_field($meta->get_name())->get_values();
                        $fieldValue = array_pop($fieldValues);
                        $locale = strtolower($matches[1]);
                        $content = trim($fieldValue->getValue());

                        $webVTT[] = [
                            'id' => md5($content),
                            'locale' => $locale,
                            'label' => $locale,
                            'content' => $content,
                        ];
                    }
                }
            }

            $exposeSubdefMappingName = $instanceId . '_subdef_mapping';
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
            // instanceId_basedID_record_id
            $assetId = $instanceId . '_' . $record->getId();

            if ($record->has_subdef($phraseanetSubdefAsDocument) && $record->get_subdef($phraseanetSubdefAsDocument)->is_physically_present()) {
                $requestBody = [
                    'publication_id' => $payload['publicationId'],
                    'description'    => $description,
                    'asset_id'       => $assetId,
                    'title'          => $record->get_title(),
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

            if (!empty($webVTT)) {
                $requestBody['webVTT'] = $webVTT;
            }

            $token = $this->getToken();

            $response = $exposeClient->post('/assets', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $requestBody
            ]);

            if ($response->getStatusCode() !==201) {
                $this->messagePublisher->pushLog("An error occurred when creating asset: status-code " . $response->getStatusCode());
                $this->finishedJob($workerRunningJob, $em, WorkerRunningJob::ERROR);

                return ;
            }

            $assetsResponse = json_decode($response->getBody(),true);

            $uploadUrl = $proxyConfig->getClientWithOptions(['verify' => $this->exposeConfiguration['verify_ssl']]);
            $uploadUrl->put($assetsResponse['uploadURL'], [
                'headers' => [
                    'Content-Type' => 'application/binary'
                ],
                'body' => fopen($record->get_subdef($phraseanetSubdefAsDocument)->getRealPath(), 'r')
            ]);

            if (count($mapping) == 0) {
                $mapping = [
                    'thumbnail'  => 'thumbnail',
                    'preview'    => 'preview',
                    'poster'     => 'poster'
                ];
            }

            // add sub-definition from mapping
            foreach ($mapping as $phraseanetSubdef => $exposeSubdef) {
                $isThumbnail = $isPreview = $isPoster = false;
                switch ($exposeSubdef) {
                    case 'thumbnail':
                        $subdefName = $exposeSubdef;
                        $isThumbnail = true;
                        break;
                    case 'preview':
                        $subdefName = $exposeSubdef;
                        $isPreview = true;
                        break;
                    case 'poster':
                        $subdefName = $exposeSubdef;
                        $isPoster = true;
                        break;
                    case 'none':
                    default:
                        $subdefName = $phraseanetSubdef;
                        break;
                }

                if ($record->has_subdef($phraseanetSubdef) && $record->get_subdef($phraseanetSubdef)->is_physically_present()) {
                    $this->postSubDefinition(
                        $exposeClient,
                        $assetsResponse['id'],
                        $record->get_subdef($phraseanetSubdef),
                        $subdefName,
                        $isPreview,
                        $isThumbnail,
                        $isPoster

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

            return;
        }

        // tell that the upload is finished
        $this->finishedJob($workerRunningJob, $em);
    }

    private function getClientAnnotationProfile(Client $exposeClient, $publicationId)
    {
        $accessToken = $this->getToken();

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

    private function postSubDefinition(Client $exposeClient, $assetId, \media_subdef $subdef, $subdefName, $isPreview = false, $isThumbnail = false, $isPoster = false)
    {
        $token = $this->getToken();

        $requestBody = [
            'asset_id'          => $assetId,
            'name'              => $subdefName,
            'use_as_preview'    => $isPreview,
            'use_as_thumbnail'  => $isThumbnail,
            'use_as_poster'     => $isPoster,
            'upload' => [
                'type' => $subdef->get_mime(),
                'size' => $subdef->get_size(),
                'name' => $subdef->get_file()

            ]
        ];

        $response = $exposeClient->post('/sub-definitions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
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

    private function getToken()
    {
        $proxyConfig = new NetworkProxiesConfiguration($this->app['conf']);

        $clientOptions = [
            'http_errors' => false,
            'verify' => $this->exposeConfiguration['verify_ssl']
        ];

        $oauthClient = $proxyConfig->getClientWithOptions($clientOptions);

        if ($this->exposeConfiguration['connection_kind'] == 'password') {
            if (!isset($this->accessTokenInfo['expires_at'])) {
                return $this->accessTokenInfo['access_token'];
            } elseif ($this->accessTokenInfo['expires_at'] > time()) {
                return $this->accessTokenInfo['access_token'];
            } elseif ($this->accessTokenInfo['expires_at'] <= time() && isset($tokenInfo['refresh_expires_at']) && $this->accessTokenInfo['refresh_expires_at'] > time()) {
                $resToken = $oauthClient->post($this->exposeConfiguration['oauth_token_uri'], [
                    'form_params' => [
                        'client_id' => $this->exposeConfiguration['auth_client_id'],
                        'client_secret' => $this->exposeConfiguration['auth_client_secret'],
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $this->accessTokenInfo['refresh_token']
                    ]
                ]);

                if ($resToken->getStatusCode() !== 200) {
                    return null;
                }

                $refreshtokenBody = $resToken->getBody()->getContents();

                $refreshtokenBody = json_decode($refreshtokenBody, true);

                // update the access token information
                $this->accessTokenInfo = [
                    'access_token' => $refreshtokenBody['access_token'],
                    'expires_at' => time() + $refreshtokenBody['expires_in'],
                    'refresh_token' => $refreshtokenBody['refresh_token'],
                    'refresh_expires_at' => time() + $refreshtokenBody['refresh_expires_in']
                ];

                return $refreshtokenBody['access_token'];
            } else {
                return null;
            }
        } elseif ($this->exposeConfiguration['connection_kind'] == 'client_credentials') {
            if (!isset($this->accessTokenInfo['expires_at'])) {
                return $this->accessTokenInfo['access_token'];
            } elseif ($this->accessTokenInfo['expires_at'] > time()) {
                return $this->accessTokenInfo['access_token'];
            } else {
                $response = $oauthClient->post($this->exposeConfiguration['oauth_token_uri'], [
                    'form_params' => [
                        'client_id'     => $this->exposeConfiguration['expose_client_id'],
                        'client_secret' => $this->exposeConfiguration['expose_client_secret'],
                        'grant_type'    => 'client_credentials'
                    ]
                ]);

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $refreshtokenBody = $response->getBody()->getContents();

                $refreshtokenBody = json_decode($refreshtokenBody,true);

                // update the access token information
                $this->accessTokenInfo = [
                    'access_token' => $refreshtokenBody['access_token'],
                    'expires_at'   => time() + $refreshtokenBody['expires_in'],
                ];

                return $refreshtokenBody['access_token'];
            }
        }
    }
}
