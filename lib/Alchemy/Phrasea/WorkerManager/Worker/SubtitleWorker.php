<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SubtitleWorker implements WorkerInterface
{
    use FilesystemAware;

    /**
     * @var callable
     */
    private $appboxLocator;

    private $logger;
    private $conf;

    /** @var WorkerRunningJobRepository  $repoWorker*/
    private $repoWorker;

    private $dispatcher;
    /**
     * @var Client
     */
    private $happyscribeClient;
    private $happyscribeToken;
    private $extension;
    private $workerRunningJob;
    private $transcriptionsId;

    public function __construct(WorkerRunningJobRepository $repoWorker, PropertyAccess $conf, callable $appboxLocator, LoggerInterface $logger, EventDispatcherInterface $dispatcher)
    {
        $this->repoWorker    = $repoWorker;
        $this->conf          = $conf;
        $this->appboxLocator = $appboxLocator;
        $this->logger        = $logger;
        $this->dispatcher    = $dispatcher;
    }

    public function process(array $payload)
    {
        $this->happyscribeToken       = $this->conf->get(['externalservice', 'happyscribe', 'token']);
        $organizationId         = $this->conf->get(['externalservice', 'happyscribe', 'organization_id']);
        $happyscribeTranscriptFormat  = $this->conf->get(['externalservice', 'happyscribe', 'transcript_format'], 'vtt');

        if (!$organizationId || !$this->happyscribeToken ) {
            $this->logger->error("External service Ginga not set correctly in configuration.yml");

            return 0;
        }

        $this->workerRunningJob = null;
        $em = $this->repoWorker->getEntityManager();

        $em->beginTransaction();

        try {
            $message = [
                'message_type'  => MessagePublisher::SUBTITLE_TYPE,
                'payload'       => $payload
            ];

            $date = new \DateTime();
            $this->workerRunningJob = new WorkerRunningJob();
            $this->workerRunningJob
                ->setDataboxId($payload['databoxId'])
                ->setRecordId($payload['recordId'])
                ->setWork(MessagePublisher::SUBTITLE_TYPE)
                ->setWorkOn("record")
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
                ->setPayload($message)
            ;

            $em->persist($this->workerRunningJob);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }

        if (in_array(strtolower($happyscribeTranscriptFormat), ['srt', 'txt', 'json', 'vtt'])) {
            $this->extension = strtolower($happyscribeTranscriptFormat);
        } else {
            $this->extension = 'vtt';
        }

        $record = $this->getApplicationBox()->get_databox($payload['databoxId'])->get_record($payload['recordId']);

        // if subdef_source not set, by default use the preview permalink
        $subdefSource = $this->conf->get(['externalservice', 'happyscribe', 'subdef_source']) ?: 'preview';

        $tmpUrl = '';
        if ($this->isPhysicallyPresent($record, $subdefSource) && ($previewLink = $record->get_subdef($subdefSource)->get_permalink()) != null) {
            $tmpUrl = $previewLink->get_url()->__toString();
        }

        $this->happyscribeClient = new Client();

        if (!$this->isRemoteFileExist($tmpUrl)) {
            $fileName = $record->get_subdef($subdefSource)->get_file();

            // first get a signed URL
            $responseUpload = $this->happyscribeClient->get('https://www.happyscribe.com/api/v1/uploads/new?filename=' . $fileName, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->happyscribeToken
                ]
            ]);

            if ($responseUpload->getStatusCode() !== 200) {
                $this->logger->error("error when uploading file to transcript,response status : ". $responseUpload->getStatusCode());
                $this->jobFinished();

                return 0;
            }

            $responseUploadBody = $responseUpload->getBody()->getContents();
            $responseUploadBody = json_decode($responseUploadBody,true);

            $tmpUrl = $responseUploadBody['signedUrl'];
            $res = $this->happyscribeClient->put($tmpUrl, [
                'body' => fopen($record->get_subdef($subdefSource)->getRealPath(), 'r')
            ]);

            if ($res->getStatusCode() !== 200) {
                $this->logger->error("error when uploading file to signed url,response status : ". $res->getStatusCode());
                $this->jobFinished();

                return 0;
            }
        }

        // create a transcription

        try {
            $responseTranscription = $this->happyscribeClient->post('https://www.happyscribe.com/api/v1/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer '. $this->happyscribeToken
                ],
                'json' => [
                    'transcription' => [
                        'name'              => $record->get_title(),
                        'is_subtitle'       => true,
                        'language'          => $payload['languageSource'],
                        'organization_id'   => $organizationId,
                        'tmp_url'           => $tmpUrl
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error("error when creating transcript : " . $e->getMessage());
            $this->jobFinished();

            return 0;
        }

        if ($responseTranscription->getStatusCode() !== 200) {
            $this->logger->error("error when creating transcript,response status : ". $responseTranscription->getStatusCode());
            $this->jobFinished();

            return 0;
        }

        $responseTranscriptionBody = $responseTranscription->getBody()->getContents();
        $responseTranscriptionBody = json_decode($responseTranscriptionBody,true);
        $this->transcriptionsId[] = $transcriptionId = $responseTranscriptionBody['id'];

        // check transcription status
        $failureTranscriptMessage = '';

        do {
            // first wait 5 second before check transcript status
            sleep(5);
            $resCheckTranscript = $this->happyscribeClient->get('https://www.happyscribe.com/api/v1/transcriptions/' . $transcriptionId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->happyscribeToken
                ]
            ]);

            if ($resCheckTranscript->getStatusCode() !== 200) {
                $this->logger->error("error when checking transcript,response status : ". $responseTranscription->getStatusCode());
                $this->jobFinished();

                return 0;
            }

            $resCheckTranscriptBody = $resCheckTranscript->getBody()->getContents();
            $resCheckTranscriptBody = json_decode($resCheckTranscriptBody,true);
            $transcriptStatus = $resCheckTranscriptBody['state'];
            if (isset($resCheckTranscriptBody['failureMessage'])) {
                $failureTranscriptMessage = $resCheckTranscriptBody['failureMessage'];
            }

        } while(!in_array($transcriptStatus, ['automatic_done', 'locked', 'failed']));

        if ($transcriptStatus != "automatic_done") {
            $this->logger->error("error when checking transcript, : " . $failureTranscriptMessage);
            $this->jobFinished();

            return 0;
        }


        $metadatas = [];

        foreach ($payload['languageDestination'] as $language =>  $metaStructureIdDestination) {
            $languageDestination = strtolower($language);

            if ($this->getTargetLanguageByCode($payload['languageSource']) == $languageDestination) {
                $metadatas[] = [
                    'meta_struct_id' => (int)$metaStructureIdDestination,
                    'meta_id'        => '',
                    'value'          => $this->exportTranscription($transcriptionId)
                ];
            } else {
                $metadatas[] = [
                    'meta_struct_id' => (int)$metaStructureIdDestination,
                    'meta_id'        => '',
                    'value'          => $this->translate($transcriptionId, $languageDestination)
                ];
            }
        }

        try {
            $record->set_metadatas($metadatas);

            // order to write meta in file
            $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));

            $this->getEventsManager()->notify(
                $payload['authenticatedUserId'],
                'eventsmanager_notify_subtitle',
                json_encode([
                    'translateMessage' => 'notification:: subtitle "%langues%" generated for "%title%" !',
                    'langues'          => implode(', ', array_keys($payload['languageDestination'])),
                    'title'            => htmlentities($record->get_title())
                ]),
                false
            );

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->jobFinished();

            return 0;
        }

        $this->logger->info("Translate subtitle on language destination SUCCESS");

        // delete transcription

//        foreach ($this->transcriptionsId as $transcriptionId) {
//            $this->deleteTranscription($transcriptionId);
//        }

        $this->jobFinished();

        return 0;
    }

    /**
     * @return \appbox
     */
    private function getApplicationBox()
    {
        $callable = $this->appboxLocator;

        return $callable();
    }

    private function jobFinished()
    {
        if ($this->workerRunningJob != null) {
            $this->workerRunningJob->setStatus(WorkerRunningJob::FINISHED)
                ->setFinished(new \DateTime('now'));

            $em = $this->repoWorker->getEntityManager();
            $this->repoWorker->reconnect();

            $em->persist($this->workerRunningJob);
            $em->flush();
        }
    }

    private function isRemoteFileExist($fileUrl)
    {
        $client = new Client();

        try {
            $client->head($fileUrl);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isPhysicallyPresent(\record_adapter $record, $subdefName)
    {
        try {
            return $record->get_subdef($subdefName)->is_physically_present();
        }
        catch (\Exception $e) {
            unset($e);
        }

        return false;
    }

    private function exportTranscription($transcriptionId)
    {
        $subtitleTranscriptTemporaryFile = $this->getTemporaryFilesystem()->createTemporaryFile("subtitle", null, $this->extension);

        $resExport = $this->happyscribeClient->post('https://www.happyscribe.com/api/v1/exports', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->happyscribeToken
            ],
            'json' => [
                'export' => [
                    'format' => $this->extension,
                    'transcription_ids' => [
                        $transcriptionId
                    ]
                ]
            ]
        ]);

        if ($resExport->getStatusCode() !== 200) {
            $this->logger->error("error when creating transcript export, response status : ". $resExport->getStatusCode());
            $this->jobFinished();

            return 0;
        }

        $resExportBody = $resExport->getBody()->getContents();
        $resExportBody = json_decode($resExportBody,true);

        $exportId = $resExportBody['id'];
        $failureExportMessage = '';

        // retrieve transcript export when ready
        do {
            sleep(3);
            $resCheckExport = $this->happyscribeClient->get('https://www.happyscribe.com/api/v1/exports/' . $exportId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->happyscribeToken
                ]
            ]);

            if ($resCheckExport->getStatusCode() !== 200) {
                $this->logger->error("error when checking transcript export ,response status : ". $resCheckExport->getStatusCode());
                $this->jobFinished();

                return 0;
            }

            $resCheckExportBody = $resCheckExport->getBody()->getContents();
            $resCheckExportBody = json_decode($resCheckExportBody,true);
            $exportStatus = $resCheckExportBody['state'];
            if (isset($resCheckExportBody['failureMessage'])) {
                $failureExportMessage = $resCheckExportBody['failureMessage'];
            }

        } while(!in_array($exportStatus, ['ready', 'expired', 'failed']));


        if ($exportStatus != 'ready') {
            $this->logger->error("error when exporting transcript : " . $failureExportMessage);
            $this->jobFinished();

            return 0;
        }

        $this->happyscribeClient->get($resCheckExportBody['download_link'], [
            'sink' => $subtitleTranscriptTemporaryFile
        ]);

        $transcriptContent = file_get_contents($subtitleTranscriptTemporaryFile);

        return $transcriptContent;
    }

    private function translate($sourceTranscriptionId, $targetLanguage)
    {
        // translate
        try {
            $resTranslate = $this->happyscribeClient->post('https://www.happyscribe.com/api/v1/task/transcription_translation', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->happyscribeToken
                ],
                'json' => [
                    'source_transcription_id' => $sourceTranscriptionId,
                    'target_language'         => strtolower($targetLanguage)
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error("error when translate : ". $e->getMessage());
            $this->jobFinished();

            return 0;
        }


        if ($resTranslate->getStatusCode() !== 200) {
            $this->logger->error("error when translate, response status : ". $resTranslate->getStatusCode());
            $this->jobFinished();

            return 0;
        }

        $resTranslateBody = $resTranslate->getBody()->getContents();
        $resTranslateBody = json_decode($resTranslateBody,true);

        if ($resTranslateBody['state'] == 'failed') {
            $this->logger->error("failed when translate, : " . $resTranslateBody['failureReason']);
            $this->jobFinished();

            return 0;
        }

        $translateId = $resTranslateBody['id'];
        $this->transcriptionsId[] = $translatedTranscriptionId = $resTranslateBody['translatedTranscriptionId'];

        // check translation
        $failureTranslateMessage = '';

        do {
            sleep(5);

            $resCheckTranslate = $this->happyscribeClient->get('https://www.happyscribe.com/api/v1/task/transcription_translation/' . $translateId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->happyscribeToken
                ]
            ]);

            if ($resCheckTranslate->getStatusCode() !== 200) {
                $this->logger->error("error when checking translation task ,response status : " . $resCheckTranslate->getStatusCode());
                $this->jobFinished();

                return 0;
            }

            $resCheckTranslateBody = $resCheckTranslate->getBody()->getContents();
            $resCheckTranslateBody = json_decode($resCheckTranslateBody,true);
            $checkTranslateStatus = $resCheckTranslateBody['state'];
            if (isset($resCheckTranslateBody['failureReason'])) {
                $failureTranslateMessage = $resCheckTranslateBody['failureReason'];
            }

        } while(!in_array($checkTranslateStatus, ['done', 'failed']));

        if ($checkTranslateStatus != 'done') {
            $this->logger->error("error when translate : " . $failureTranslateMessage);
            $this->jobFinished();

            return 0;
        }

        // export the translation now

        return $this->exportTranscription($translatedTranscriptionId);
    }

    private function deleteTranscription($transcriptionId)
    {
        $this->happyscribeClient->delete('https://www.happyscribe.com/api/v1/transcriptions/' . $transcriptionId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->happyscribeToken
            ]
        ]);
    }

    private function getTargetLanguageByCode($code)
    {
        $t = explode('-', $code);

        return $t[0];
    }

    /**
     * @return \eventsmanager_broker
     */
    private function getEventsManager()
    {
        $app = $this->getApplicationBox()->getPhraseApplication();

        return $app['events-manager'];
    }

}
