<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerJob;
use Alchemy\Phrasea\Model\Repositories\WorkerJobRepository;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class SubtitleWorker implements WorkerInterface
{
    /**
     * @var callable
     */
    private $appboxLocator;

    private $logger;
    private $conf;

    /** @var WorkerJobRepository  $repoWorkerJob*/
    private $repoWorkerJob;

    public function __construct(WorkerJobRepository $repoWorkerJob, PropertyAccess $conf, callable $appboxLocator, LoggerInterface $logger)
    {
        $this->repoWorkerJob = $repoWorkerJob;
        $this->conf          = $conf;
        $this->appboxLocator = $appboxLocator;
        $this->logger        = $logger;
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

        $record = $this->getApplicationBox()->get_databox($payload['databoxId'])->get_record($payload['recordId']);

        if ($payload['permalinkUrl'] != '' && $payload['metaStructureId']) {
            switch ($payload['langageSource']) {
                case 'En':
                    $language = 'en-GB';
                    break;
                case 'De':
                    $language = 'de-DE';
                    break;
                case 'Fr':
                default:
                    $language = 'fr-FR';
                    break;
            }

            $gingerClient = new Client();

            try {
                $response = $gingerClient->post($gingaBaseurl.'/media/', [
                    'headers' => [
                        'Authorization' => 'token '.$gingaToken
                    ],
                    'json' => [
                        'url'       => $payload['permalinkUrl'],
                        'language'  => $language
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
                        'language'  => $language
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

            $metadatas[0] = [
                'meta_struct_id' => (int)$payload['metaStructureId'],
                'meta_id'        => '',
                'value'          => $transcriptContent
            ];

            try {
                $record->set_metadatas($metadatas);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->jobFinished($workerJob);

                return 0;
            }

            $this->logger->info("Auto subtitle SUCCESS");
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
}
