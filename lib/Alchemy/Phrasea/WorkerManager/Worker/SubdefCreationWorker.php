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

        try {
            $databox = $this->findDataboxById($databoxId);
            $record = $databox->get_record($recordId);
        } catch (\Exception $e) {
            $this->logger->error(sprintf("%s (%s) : record not found %s", __FILE__, __LINE__, $e->getMessage()));

            return;
        }

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

            return ;
        }

        // here we can work

        $this->subdefGenerator->setLogger($this->logger);

        try {
            $this->subdefGenerator->generateSubdefs($record, [$subdefName]);
        }
        catch (Exception $e) {
            $this->logger->error("Exception catched: " . $e->getMessage());
        }
        catch (Throwable $e) {
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;
            $workerMessage = "Exception throwable catched when create subdef for the recordID: " .$recordId;

            $this->logger->error($workerMessage);

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                $record,
                $subdefName,
                $workerMessage,
                $count,
                $workerRunningJobId
            ));

            // the subscriber will "unlock" the row, no need to do it here
            return ;
        }

        // begin to check if the subdef is successfully generated
        $subdef = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())->getSubdef($subdefName);
        $filePathToCheck = null;

        if ($record->has_subdef($subdefName) ) {
            $filePathToCheck = $record->get_subdef($subdefName)->getRealPath();
        }

        $filePathToCheck = $this->filesystem->generateSubdefPathname($record, $subdef, $filePathToCheck);

        if (!$this->filesystem->exists($filePathToCheck)) {
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
                $record,
                $subdefName,
                'Subdef generation failed !',
                $count,
                $workerRunningJobId
            ));

            $this->subdefGenerator->setLogger($oldLogger);

            // the subscriber will "unlock" the row, no need to do it here
            return ;
        }

        // checking ended

        // order to write meta for the subdef if needed
        /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionWritemeta() */
        $this->dispatcher->dispatch(
            WorkerEvents::SUBDEFINITION_WRITE_META,
            new SubdefinitionWritemetaEvent(
                $record,
                $subdefName
            )
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

        $this->messagePublisher->pushLog(sprintf("subdefinition created %s databoxname=%s databoxid=%d recordid=%d",
            $payload['subdefName'], $databox->get_viewname(), $databoxId, $recordId));

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
