<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerJob;
use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
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

    /** @var WorkerJobRepository  $repoWorkerJob*/
    private $repoWorkerJob;

    private $dispatcher;

    public function __construct(WorkerJobRepository $repoWorkerJob, PropertyAccess $conf, callable $appboxLocator, LoggerInterface $logger, EventDispatcherInterface $dispatcher)
    {
        $this->repoWorkerJob = $repoWorkerJob;
        $this->conf          = $conf;
        $this->appboxLocator = $appboxLocator;
        $this->logger        = $logger;
        $this->dispatcher    = $dispatcher;
    }

    public function process(array $payload)
    {
        $gingaBaseurl           = $this->conf->get(['externalservice', 'ginger', 'AutoSubtitling', 'service_base_url']);
        $gingaToken             = $this->conf->get(['externalservice', 'ginger', 'AutoSubtitling', 'token']);
        $gingaTranscriptFormat  = $this->conf->get(['externalservice', 'ginger', 'AutoSubtitling', 'transcript_format']);

        if (!$gingaBaseurl || !$gingaToken || !$gingaTranscriptFormat) {
            $this->logger->error("External service Ginga not set correctly in configuration.yml");

            return 0;
        }

        /** @var WorkerJob $workerJob */
        $workerJob = $this->repoWorkerJob->find($payload['workerId']);
        if ($workerJob == null) {
            $this->logger->error("WorkerId not found");

            return 0;
        }

        $workerJob->setStatus(WorkerJob::RUNNING)
            ->setStarted(new \DateTime('now'));

        $em = $this->repoWorkerJob->getEntityManager();
        $this->repoWorkerJob->reconnect();
        $em->persist($workerJob);
        $em->flush();

        switch ($gingaTranscriptFormat) {
            case 'text/srt,':
                $extension = 'srt';
                break;
            case 'text/plain':
                $extension = 'txt';
                break;
            case 'application/json':
                $extension = 'json';
                break;
            case 'text/vtt':
            default:
                $extension = 'vtt';
                break;
        }

        $languageSource         = $this->getLanguageFormat($payload['languageSource']);
        $languageDestination    = $this->getLanguageFormat($payload['languageDestination']);

        $record = $this->getApplicationBox()->get_databox($payload['databoxId'])->get_record($payload['recordId']);
        $languageSourceFieldName = $record->getDatabox()->get_meta_structure()->get_element($payload['metaStructureIdSource'])->get_name();

        $subtitleSourceTemporaryFile = $this->getTemporaryFilesystem()->createTemporaryFile("subtitle", null, $extension);
        $gingerClient = new Client();

        // if the languageSourceFieldName do not yet exist, first generate subtitle for it
        if ($payload['permalinkUrl'] != '' && !$record->get_caption()->has_field($languageSourceFieldName)) {
            try {
                $response = $gingerClient->post($gingaBaseurl.'/media/', [
                    'headers' => [
                        'Authorization' => 'token '.$gingaToken
                    ],
                    'json' => [
                        'url'       => $payload['permalinkUrl'],
                        'language'  => $languageSource
                    ]
                ]);
            } catch(\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->jobFinished($workerJob);

                return 0;
            }

            if ($response->getStatusCode() !== 201) {
                $this->logger->error("response status /media/ : ". $response->getStatusCode());
                $this->jobFinished($workerJob);

                return 0;
            }

            $responseMediaBody = $response->getBody()->getContents();
            $responseMediaBody = json_decode($responseMediaBody,true);

            $checkStatus = true;
            do {
                // first wait 5 second before check subtitling status
                sleep(5);
                $this->logger->info("bigin to check status");
                try {
                    $response = $gingerClient->get($gingaBaseurl.'/task/'.$responseMediaBody['task_id'].'/', [
                        'headers' => [
                            'Authorization' => 'token '.$gingaToken
                        ]
                    ]);
                } catch (\Exception $e) {
                    $checkStatus = false;

                    break;
                }

                if ($response->getStatusCode() !== 200) {
                    $checkStatus = false;

                    break;
                }

                $responseTaskBody = $response->getBody()->getContents();
                $responseTaskBody = json_decode($responseTaskBody,true);

            } while($responseTaskBody['status'] != 'SUCCESS');

            if (!$checkStatus) {
                $this->logger->error("can't check status");
                $this->jobFinished($workerJob);

                return 0;
            }

            try {
                $response = $gingerClient->get($gingaBaseurl.'/media/'.$responseMediaBody['media']['uuid'].'/', [
                    'headers' => [
                        'Authorization' => 'token '.$gingaToken,
                        'ACCEPT'        => $gingaTranscriptFormat
                    ],
                    'query' => [
                        'language'  => $languageSource
                    ]
                ]);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->jobFinished($workerJob);

                return 0;
            }

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("response status /media/uuid : ". $response->getStatusCode());
                $this->jobFinished($workerJob);

                return 0;
            }

            $transcriptContent = $response->getBody()->getContents();

            $transcriptContent = preg_replace('/WEBVTT/', 'WEBVTT - with cue identifier', $transcriptContent, 1);

            // save subtitle on temporary file to use to translate if needed
            file_put_contents($subtitleSourceTemporaryFile, $transcriptContent);

            $metadatas[0] = [
                'meta_struct_id' => (int)$payload['metaStructureIdSource'],
                'meta_id'        => '',
                'value'          => $transcriptContent
            ];

            try {
                $record->set_metadatas($metadatas);

                // order to write meta in file
                $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
                    new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->jobFinished($workerJob);

                return 0;
            }

            $this->logger->info("Generate subtitle on language source SUCCESS");
        } elseif ($record->get_caption()->has_field($languageSourceFieldName)) {
            // get the source subtitle and save it to a temporary file
            $fieldValues = $record->get_caption()->get_field($languageSourceFieldName)->get_values();
            $fieldValue = array_pop($fieldValues);

            file_put_contents($subtitleSourceTemporaryFile, $fieldValue->getValue());
        }

        if ($payload['metaStructureIdSource'] !== $payload['metaStructureIdDestination']) {
            try {
                $response = $gingerClient->post($gingaBaseurl.'/translate/', [
                    'headers' => [
                        'Authorization' => 'token '.$gingaToken,
                        'ACCEPT'        => $gingaTranscriptFormat
                    ],
                    'multipart' => [
                        [
                            'name'      => 'transcript',
                            'contents'  => fopen($subtitleSourceTemporaryFile, 'r')
                        ],
                        [
                            'name'      => 'transcript_format',
                            'contents'  => $gingaTranscriptFormat,

                        ],
                        [
                            'name'      => 'language_in',
                            'contents'  => $languageSource,

                        ],
                        [
                            'name'      => 'language_out',
                            'contents'  => $languageDestination,

                        ]
                    ]
                ]);
            } catch(\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->jobFinished($workerJob);

                return 0;
            }

            if ($response->getStatusCode() !== 200) {
                $this->logger->error("response status /translate/ : ". $response->getStatusCode());
                $this->jobFinished($workerJob);

                return 0;
            }

            $transcriptContent = $response->getBody()->getContents();
            $transcriptContent = preg_replace('/WEBVTT/', 'WEBVTT - with cue identifier', $transcriptContent, 1);

            $metadatas[0] = [
                'meta_struct_id' => (int)$payload['metaStructureIdDestination'],
                'meta_id'        => '',
                'value'          => $transcriptContent
            ];

            try {
                $record->set_metadatas($metadatas);

                // order to write meta in file
                $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
                    new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->jobFinished($workerJob);

                return 0;
            }

            $this->logger->info("Translate subtitle on language destination SUCCESS");
        }

        $this->jobFinished($workerJob);

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

    private function jobFinished(WorkerJob $workerJob)
    {
        $workerJob->setStatus(WorkerJob::FINISHED)
            ->setFinished(new \DateTime('now'));

        $em = $this->repoWorkerJob->getEntityManager();
        $this->repoWorkerJob->reconnect();

        $em->persist($workerJob);
        $em->flush();
    }

    private function getLanguageFormat($language)
    {
        switch ($language) {
            case 'En':
                return 'en-GB';
            case 'De':
                return 'de-DE';
            case 'Fr':
            default:
                return 'fr-FR';
        }
    }
}
