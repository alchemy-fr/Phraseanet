<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\WorkerManager\Event\StoryCreateCoverEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionCreationFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionWritemetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SubdefCreationWorker implements WorkerInterface
{
    use ApplicationBoxAware;

    private $subdefGenerator;

    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    private $logger;
    private $dispatcher;
    private $filesystem;
    private $repoWorker;
    private $indexer;

    public function __construct(
        SubdefGenerator $subdefGenerator,
        MessagePublisher $messagePublisher,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        FilesystemService $filesystem,
        WorkerRunningJobRepository $repoWorker,
        Indexer $indexer
    )
    {
        $this->subdefGenerator  = $subdefGenerator;
        $this->messagePublisher = $messagePublisher;
        $this->logger           = $logger;
        $this->dispatcher       = $dispatcher;
        $this->filesystem       = $filesystem;
        $this->repoWorker       = $repoWorker;
        $this->indexer          = $indexer;
    }

    public function process(array $payload)
    {
        if (isset($payload['recordId']) && isset($payload['databoxId'])) {
            $recordId       = $payload['recordId'];
            $databoxId      = $payload['databoxId'];
            $wantedSubdef   = [$payload['subdefName']];

            $databox = $this->findDataboxById($databoxId);
            $record = $databox->get_record($recordId);

            $oldLogger = $this->subdefGenerator->getLogger();

            $message = [
                'message_type'  => MessagePublisher::SUBDEF_CREATION_TYPE,
                'payload'       => $payload
            ];

            if (!$record->isStory()) {
                // check if there is a write meta running for the record or the same task running
                $canCreateSubdef = $this->repoWorker->canCreateSubdef($payload['subdefName'], $recordId, $databoxId);

                if (!$canCreateSubdef) {
                    // the file is in used to write meta

                    $this->messagePublisher->publishDelayedMessage($message, MessagePublisher::SUBDEF_CREATION_TYPE);

                    return ;
                }

                // tell that a file is in used to create subdef
                $em = $this->repoWorker->getEntityManager();
                $this->repoWorker->reconnect();

                if (isset($payload['workerJobId'])) {
                    /** @var WorkerRunningJob $workerRunningJob */
                    $workerRunningJob = $this->repoWorker->find($payload['workerJobId']);

                    if ($workerRunningJob == null) {
                        $this->logger->error("Given workerJobId not found !");

                        return ;
                    }
                    
                    $workerRunningJob
                        ->setInfo(WorkerRunningJob::ATTEMPT . $payload['count'])
                        ->setStatus(WorkerRunningJob::RUNNING);

                    $em->persist($workerRunningJob);

                    $em->flush();

                } else {
                    $em->beginTransaction();
                    try {
                        $date = new \DateTime();
                        $workerRunningJob = new WorkerRunningJob();
                        $workerRunningJob
                            ->setDataboxId($databoxId)
                            ->setRecordId($recordId)
                            ->setWork(MessagePublisher::SUBDEF_CREATION_TYPE)
                            ->setWorkOn($payload['subdefName'])
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

                $this->subdefGenerator->setLogger($this->logger);

                try {
                    $this->subdefGenerator->generateSubdefs($record, $wantedSubdef);
                } catch (\Exception $e) {
                    $this->logger->error("Exception catched: " . $e->getMessage());

                } catch (\Throwable $e) {
                    $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;
                    $workerMessage = "Exception throwable catched when create subdef for the recordID: " .$recordId;

                    $this->logger->error($workerMessage);

                    $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                        $record,
                        $payload['subdefName'],
                        $workerMessage,
                        $count,
                        $workerRunningJob->getId()
                    ));

                    return ;
                }

                // begin to check if the subdef is successfully generated
                $subdef = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())->getSubdef($payload['subdefName']);
                $filePathToCheck = null;

                if ($record->has_subdef($payload['subdefName']) ) {
                    $filePathToCheck = $record->get_subdef($payload['subdefName'])->getRealPath();
                }

                $filePathToCheck = $this->filesystem->generateSubdefPathname($record, $subdef, $filePathToCheck);

                if (!$this->filesystem->exists($filePathToCheck)) {

                    $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

                    $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                        $record,
                        $payload['subdefName'],
                        'Subdef generation failed !',
                        $count,
                        $workerRunningJob->getId()
                    ));

                    $this->subdefGenerator->setLogger($oldLogger);
                    return ;
                }
                // checking ended

                // order to write meta for the subdef if needed
                $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_WRITE_META, new SubdefinitionWritemetaEvent(
                    $record,
                    $payload['subdefName'])
                );

                $this->subdefGenerator->setLogger($oldLogger);

                //  update jeton when subdef is created
                $this->updateJeton($record);

                $parents = $record->get_grouping_parents();

                //  create a cover for a story
                //  used when uploaded via uploader-service and grouped as a story
                if (!$parents->is_empty() && isset($payload['status']) && $payload['status'] == MessagePublisher::NEW_RECORD_MESSAGE  && in_array($payload['subdefName'], array('thumbnail', 'preview'))) {
                    foreach ($parents->get_elements() as $story) {
                        if (self::checkIfFirstChild($story, $record)) {
                            $data = implode('_', [$databoxId, $story->getRecordId(), $recordId, $payload['subdefName']]);

                            $this->dispatcher->dispatch(WorkerEvents::STORY_CREATE_COVER, new StoryCreateCoverEvent($data));
                        }
                    }
                }

                // update elastic
                $this->indexer->flushQueue();

                // tell that we have finished to work on this file
                $this->repoWorker->reconnect();
                $em->getConnection()->beginTransaction();
                try {
                    $workerRunningJob->setStatus(WorkerRunningJob::FINISHED);
                    $workerRunningJob->setFinished(new \DateTime('now'));
                    $em->persist($workerRunningJob);
                    $em->flush();
                    $em->commit();
                } catch (\Exception $e) {
                    try {
                        $em->getConnection()->beginTransaction();
                        $workerRunningJob->setStatus(WorkerRunningJob::FINISHED);
                        $em->persist($workerRunningJob);
                        $em->flush();
                        $em->commit();
                    } catch (\Exception $e) {
                        $this->messagePublisher->pushLog("rollback on recordID :" . $workerRunningJob->getRecordId());
                        $em->rollback();
                    }

                }
            }
        }
    }

    public static function checkIfFirstChild(\record_adapter $story, \record_adapter $record)
    {
        $sql = "SELECT * FROM regroup WHERE rid_parent = :parent_record_id AND rid_child = :children_id and ord = :ord";

        $connection = $record->getDatabox()->get_connection();

        $stmt = $connection->prepare($sql);

        $stmt->execute([
            ':parent_record_id' => $story->getRecordId(),
            ':children_id'      => $record->getRecordId(),
            ':ord'              => 0,
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if ($row) {
            return true;
        }

        return false;
    }

    private function updateJeton(\record_adapter $record)
    {
        $connection = $record->getDatabox()->get_connection();
        $connection->beginTransaction();

        // mark subdef created
        $sql = 'UPDATE record'
            . ' SET jeton=(jeton & ~(:token)), moddate=NOW()'
            . ' WHERE record_id=:record_id';

        $stmt = $connection->prepare($sql);

        $stmt->execute([
            ':record_id'    => $record->getRecordId(),
            ':token'        => PhraseaTokens::MAKE_SUBDEF,
        ]);

        $connection->commit();
        $stmt->closeCursor();
    }
}
