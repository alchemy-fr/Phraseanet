<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\WorkerManager\Event\StoryCreateCoverEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionCreationFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\SubdefinitionWritemetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

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
        // mandatory args
        if (!isset($payload['recordId']) || !isset($payload['databoxId']) || !isset($payload['subdefName'])) {
            // bad payload
            $this->logger->error(sprintf("%s (%s) : bad payload", __FILE__, __LINE__));
            return;
        }

        $recordId       = $payload['recordId'];
        $databoxId      = $payload['databoxId'];
        $subdefName     = $payload['subdefName'];

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("process SubdefCreation for %s.%s.%s", $databoxId, $recordId, $subdefName)
        ), FILE_APPEND | LOCK_EX);

        $databox = $this->findDataboxById($databoxId);
        $record = $databox->get_record($recordId);

        if ($record->isStory()) {
            return;
        }

        $oldLogger = $this->subdefGenerator->getLogger();

        // try to "lock" the file, will return null if already locked
        $workerRunningJobId = $this->repoWorker->canCreateSubdef($payload);

        if (is_null($workerRunningJobId)) {
            // the file is written by another worker, delay to retry later
            $this->messagePublisher->publishDelayedMessage(
                [
                    'message_type'  => MessagePublisher::SUBDEF_CREATION_TYPE,
                    'payload'       => $payload
                ],
                MessagePublisher::SUBDEF_CREATION_TYPE
            );
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("cannot CreateSubdef for %s.%s.%s, delayed", $databoxId, $recordId, $subdefName)
            ), FILE_APPEND | LOCK_EX);

            return ;
        }

        if (filesize($record->get_subdef('document')->getRealPath()) < $payload['documentFileSize']) {
            // the file size is less than expected so retried

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;
            $workerMessage = sprintf("Can't create subdef because the document source fileSize %s less than %s expected, so retried!", filesize($record->get_subdef('document')->getRealPath()), $payload['documentFileSize']);

            $this->logger->error($workerMessage);

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                $record,
                $subdefName,
                $payload['documentFileSize'],
                $workerMessage,
                $count,
                $workerRunningJobId
            ));

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                $workerMessage
            ), FILE_APPEND | LOCK_EX);

            // the subscriber will "unlock" the row, no need to do it here
            return ;
        }

        // here we can work

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("ready to CreateSubdef for %s.%s.%s", $databoxId, $recordId, $subdefName)
        ), FILE_APPEND | LOCK_EX);

        $this->subdefGenerator->setLogger($this->logger);

        try {
            $this->subdefGenerator->generateSubdefs($record, [$subdefName]);

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("subdef generated for %s.%s.%s (?)", $databoxId, $recordId, $subdefName)
            ), FILE_APPEND | LOCK_EX);

        }
        catch (Exception $e) {
            $this->logger->error("Exception catched: " . $e->getMessage());

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("!!! subdef generation failed (ignored) for %s.%s.%s : %s", $databoxId, $recordId, $subdefName, $e->getMessage())
            ), FILE_APPEND | LOCK_EX);

        }
        catch (Throwable $e) {
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("subdef generation failed, retry delayed for %s.%s.%s : %s", $databoxId, $recordId, $subdefName, $e->getMessage())
            ), FILE_APPEND | LOCK_EX);

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;
            $workerMessage = "Exception throwable catched when create subdef for the recordID: " .$recordId;

            $this->logger->error($workerMessage);

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                $record,
                $subdefName,
                $payload['documentFileSize'],
                $workerMessage,
                $count,
                $workerRunningJobId
            ));

            // the subscriber will "unlock" the row, no need to do it here
            return ;
        }

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("checking subdef file for %s.%s.%s", $databoxId, $recordId, $subdefName)
        ), FILE_APPEND | LOCK_EX);

        // begin to check if the subdef is successfully generated
        $subdef = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())->getSubdef($subdefName);
        $filePathToCheck = null;

        if ($record->has_subdef($subdefName) ) {
            $filePathToCheck = $record->get_subdef($subdefName)->getRealPath();

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("record->has_subdef(%s)=true for %s.%s : check \"%s\"", $subdefName, $databoxId, $recordId, $filePathToCheck)
            ), FILE_APPEND | LOCK_EX);
        }
        else {
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("record->has_subdef(%s)=false for %s.%s", $subdefName, $databoxId, $recordId)
            ), FILE_APPEND | LOCK_EX);
        }

        $filePathToCheck = $this->filesystem->generateSubdefPathname($record, $subdef, $filePathToCheck);

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("checking \"%s\" for %s.%s.%s", $filePathToCheck, $databoxId, $recordId, $subdefName)
        ), FILE_APPEND | LOCK_EX);

        if (!$this->filesystem->exists($filePathToCheck)) {

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("!!! subdef file \"%s\" missing, retry delayed for %s.%s.%s", $filePathToCheck, $databoxId, $recordId, $subdefName)
            ), FILE_APPEND | LOCK_EX);

            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                $record,
                $subdefName,
                $payload['documentFileSize'],
                'Subdef generation failed !',
                $count,
                $workerRunningJobId
            ));

            $this->subdefGenerator->setLogger($oldLogger);

            // the subscriber will "unlock" the row, no need to do it here
            return ;
        }

        // checking ended

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("subdef file exists, order to write meta for %s.%s.%s", $databoxId, $recordId, $subdefName)
        ), FILE_APPEND | LOCK_EX);

        // order to write meta for the subdef if needed
        // and the initial filesize of the subdef is getting from disk in the event
        /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionWritemeta() */
        $this->dispatcher->dispatch(
            WorkerEvents::SUBDEFINITION_WRITE_META,
            new SubdefinitionWritemetaEvent(
                $record,
                $subdefName
            )
        );

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("Event WorkerEvents::SUBDEFINITION_WRITE_META dispatched  for %s.%s.%s", $record->getDataboxId(), $record->getRecordId(), $subdefName)
        ), FILE_APPEND | LOCK_EX);

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

        // tell that we have finished to work on this file (=unlock)
        $this->repoWorker->markFinished($workerRunningJobId);

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
