<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\Autosub\Autosub;
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
        $workerRunningJob = null;
        $em = $this->repoWorker->getEntityManager();

        $em->beginTransaction();

        try {
            $message = [
                'message_type'  => MessagePublisher::SUBTITLE_TYPE,
                'payload'       => $payload
            ];

            $date = new \DateTime();
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setDataboxId($payload['databoxId'])
                ->setRecordId($payload['recordId'])
                ->setWork(MessagePublisher::SUBTITLE_TYPE)
                ->setWorkOn("record")
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
                ->setPayload($message)
            ;

            $em->persist($workerRunningJob);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }

        if ($payload['subtitleProvider'] == 'autosub') {
            $record = $this->getApplicationBox()->get_databox($payload['databoxId'])->get_record($payload['recordId']);
            $subdefSource  = $this->conf->get(['externalservice', 'autosub', 'AutoSubtitling', 'subdef_source']) ?: "preview";

            if ($record->has_subdef($subdefSource) && $record->get_subdef($subdefSource)->is_physically_present()) {
                $filePath = $record->get_subdef($subdefSource)->getRealPath();
            } else {
                $this->logger->error("The source file to use with autosub is not physically present!");
                $this->jobFinished($workerRunningJob);

                return 1;
            }

            $transcriptFormat  = $this->conf->get(['externalservice', 'autosub', 'AutoSubtitling', 'transcript_format']);
            $googleApiKey  = $this->conf->get(['externalservice', 'autosub', 'AutoSubtitling', 'google_translate_api_key']);

            switch ($transcriptFormat) {
                case 'srt':
                case 'text/srt':
                    $extension = 'srt';
                    break;
                case 'plain':
                case 'text/plain':
                    $extension = 'txt';
                    break;
                case 'json':
                case 'application/json':
                    $extension = 'json';
                    break;
                case 'text/vtt':
                default:
                    $extension = 'vtt';
                    break;
            }

            $subtitleTemporaryFile = $this->getTemporaryFilesystem()->createTemporaryFile("subtitle", null, $extension);

            $autosub = Autosub::create($this->logger, []);

            $commands = [
                '-S',
                strtolower($payload['languageSource']),
                '-D',
                strtolower($payload['languageDestination']),
                '-F',
                $extension,
                '-o',
                $subtitleTemporaryFile
            ];

            if (!empty($googleApiKey)) {
                $commands[] = '-K';
                $commands[] = $googleApiKey;
            }

            $commands[] = $filePath;

            try {
                $autosub->command($commands);
            } catch(\Exception $e) {
                $workerRunningJob->setInfo($e->getMessage());
                $this->jobFinished($workerRunningJob, WorkerRunningJob::ERROR);

                return 1;
            }

            $transcriptContent = file_get_contents($subtitleTemporaryFile);

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
                $this->jobFinished($workerRunningJob, WorkerRunningJob::ERROR);

                return 0;
            }

            $this->logger->info("Generate subtitle successfull!");
        }

        $this->jobFinished($workerRunningJob);

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

    private function jobFinished(WorkerRunningJob $workerRunningJob, $status = WorkerRunningJob::FINISHED)
    {
        if ($workerRunningJob != null) {
            $workerRunningJob->setStatus($status)
                ->setFinished(new \DateTime('now'));

            $em = $this->repoWorker->getEntityManager();
            $this->repoWorker->reconnect();

            $em->persist($workerRunningJob);
            $em->flush();
        }
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
