<?php
// declare(ticks = 5);

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
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
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Ramsey\Uuid\Uuid;

class SubdefCreationWorker implements WorkerInterface
{
    use ApplicationBoxAware;
    use DataboxLoggerAware;

    private $subdefGenerator;

    /** @var MessagePublisher $messagePublisher */
    private $messagePublisher;

    private $logger;
    private $dispatcher;
    private $filesystem;
    private $repoWorker;
    private $indexer;

    private $recordId;
    private $subdefName;
    private $workerRunningJobId = -1;
    private $count = 0;
    private $endOk = 0;

    private $wec_upid = '-';
    private $wrsc_upid = '-';

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
        $this->subdefGenerator = $subdefGenerator;
        $this->messagePublisher = $messagePublisher;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->repoWorker = $repoWorker;
        $this->indexer = $indexer;

        $this->recordId = -1;
        $this->subdefName = "?";

        $this->log("construct");

//        $ps = [];
//        exec("exec ps -faux", $ps);
//        $this->log(sprintf("ps:\n%s", join("\n", $ps)));


        register_shutdown_function(function() {
            $this->log(sprintf("shutdown"));
        });
//        $_s = [
//            SIGTERM,    // 15
//            SIGHUP,     // 1
//            SIGINT,     // 2
//            SIGQUIT,    // 3
//            SIGILL,     // 4
//            SIGABRT,    // 6
//            SIGPIPE,    // 13
////            SIGCHLD,    // 17
//// !!!        SIGSTOP,  // 19
//            SIGTSTP,    // 20
//            SIGTTIN,    // 21
//            SIGTTOU,    // 22
//        ];
//        foreach ($_s as $s) {
//            pcntl_signal($s, function (int $signo, $siginfo = null) {
//                $this->log(sprintf("signal %d", $signo), 1);
//                $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//                $this->log(sprintf("backtrace:\n%s", var_export($bt, true)), 1);
//
//                //    throw new \Exception("received signal $signo");
//
////                if( false && ($signo === SIGTERM || $signo === SIGINT)) {
////                    if ($this->workerRunningJobId !== null && $this->workerRunningJobId !== -1) {
////                        $this->repoWorker->markFinished($this->workerRunningJobId);
//////                    $this->log(sprintf("markFinished %d", $this->workerRunningJobId));
////                    }
//////                $this->messagePublisher->publishDelayedMessage(
//////                    [
//////                        'message_type'  => MessagePublisher::SUBDEF_CREATION_TYPE,
//////                        'payload'       => $payload
//////                    ],
//////                    MessagePublisher::SUBDEF_CREATION_TYPE
//////                );
////
////                    $this->log(sprintf("die"));
////                    die(1);
////                }
//            });
//        }

    }

    private function log($s = '', $depth=0)
    {
        static $t0 = null;
        $t = microtime(true);
        if($t0 === null) {
            $t0 = $t;
        }
        $dt = (int)(1000000.0*($t - $t0));
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth+1);
        $line = array_key_exists($depth, $bt) && array_key_exists('line', $bt[$depth]) ? $bt[$depth]['line'] : -1;
        $s = sprintf("%s , %s , %d , %d , %d , %d , \"%s\" , %d , %d , %d , \"%s\"\n", $this->wec_upid, $this->wrsc_upid, $this->workerRunningJobId, posix_getppid(), getmypid(), $this->recordId, $this->subdefName, $dt, $line, $this->endOk, var_export($s, true));
        $f = fopen("/var/alchemy/Phraseanet/logs/trace_scw.txt", "a");
        fwrite($f, $s);
        fflush($f);
        fclose($f);
    }



    public function process(array $payload)
    {
        // mandatory args
        if (!isset($payload['recordId']) || !isset($payload['databoxId']) || !isset($payload['subdefName'])) {
            // bad payload
            $this->logger->error(sprintf("%s (%s) : bad payload", __FILE__, __LINE__));
            $this->endOk = 1;
            return;
        }
        $recordId       = $payload['recordId'];
        $databoxId      = $payload['databoxId'];
        $subdefName     = $payload['subdefName'];
        $this->wec_upid     = $payload['wec_upid'] ?? '-';
        $this->wrsc_upid     = $payload['wrsc_upid'] ?? '-';

        $this->recordId = $recordId;
        $this->subdefName = $subdefName;
        $this->count = $payload['count'] ?? 1;

        $this->log("process");



        pcntl_signal_dispatch();

        try {
            $databox = $this->findDataboxById($databoxId);
            $record = $databox->get_record($recordId);
        } catch (\Exception $e) {

            $this->log();

            $this->logger->error(sprintf("%s (%s) : record not found %s", __FILE__, __LINE__, $e->getMessage()));
            $this->endOk = 2;
            return;
        }

        pcntl_signal_dispatch();
        //        $this->log();

        if ($record->isStory()) {
            $this->endOk = 3;
            return;
        }

        $oldLogger = $this->subdefGenerator->getLogger();

        // try to "lock" the file, will return null if already locked
       // $payload['wec_upid'] = Uuid::uuid4()->toString();
        $this->workerRunningJobId = $this->repoWorker->canCreateSubdef($payload);

        pcntl_signal_dispatch();
        //        $this->log();

        if (is_null($this->workerRunningJobId)) {
            // the file is written by another worker, delay to retry later

            pcntl_signal_dispatch();
            $this->log();

            $this->messagePublisher->publishDelayedMessage(
                [
                    'message_type'  => MessagePublisher::SUBDEF_CREATION_TYPE,
                    'payload'       => $payload
                ],
                MessagePublisher::SUBDEF_CREATION_TYPE
            );
            $this->endOk = 4;
            return ;
        }

        // here we can work


        pcntl_signal_dispatch();
        //      $this->log();

        /** @var WorkerRunningJob $workerRunningJob */
        $workerRunningJob = $this->repoWorker->find($this->workerRunningJobId);


        pcntl_signal_dispatch();
        //        $this->log();

        $this->getDataboxLogger($databox)->initOrUpdateLogDocsFromWorker($record, $databox, $workerRunningJob, $subdefName, \Session_Logger::EVENT_SUBDEFCREATION);

        $this->subdefGenerator->setLogger($this->logger);

        try {

            pcntl_signal_dispatch();
            //           $this->log();

            $this->subdefGenerator->generateSubdefs($record, [$subdefName]);

            pcntl_signal_dispatch();
            //           $this->log();

        }
        catch (Exception $e) {

            pcntl_signal_dispatch();
            $workerMessage = sprintf("Exception catched when creating subdef for the recordID: %s (count=%d) : %s", $recordId, $this->count, $e->getMessage());

            $this->logger->error("Exception catched: " . $e->getMessage());
        }
        catch (Throwable $e) {

            pcntl_signal_dispatch();

            $workerMessage = sprintf("Throwable catched when creating subdef for the recordID: %s (count=%d) : %s", $recordId, $this->count, $e->getMessage());

            $this->log($workerMessage);
            $this->logger->error($workerMessage);

//            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
//            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
//                $record,
//                $subdefName,
//                $workerMessage,
//                $this->count + 1,
//                $this->workerRunningJobId
//            ));


            pcntl_signal_dispatch();
            //            $this->log();

            // the subscriber will "unlock" the row, no need to do it here
            $this->endOk = 5;
            return ;
        }

        // begin to check if the subdef is successfully generated

        pcntl_signal_dispatch();
        //        $this->log();

        $subdef = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())->getSubdef($subdefName);
        $filePathToCheck = null;


        pcntl_signal_dispatch();
        //        $this->log();

        if ($record->has_subdef($subdefName) ) {

            pcntl_signal_dispatch();
            //            $this->log();

            $filePathToCheck = $record->get_subdef($subdefName)->getRealPath();
        }

        $filePathToCheck = $this->filesystem->generateSubdefPathname($record, $subdef, $filePathToCheck);


        pcntl_signal_dispatch();
        //       $this->log();

        if (!$this->filesystem->exists($filePathToCheck)) {

            $this->log();

//            /** @uses \Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber::onSubdefinitionCreationFailure() */
//            $this->dispatcher->dispatch(WorkerEvents::SUBDEFINITION_CREATION_FAILURE, new SubdefinitionCreationFailureEvent(
//                $record,
//                $subdefName,
//                'Subdef generation failed !',
//                $this->count + 1,
//                $this->workerRunningJobId
//            ));

            $this->subdefGenerator->setLogger($oldLogger);

            // the subscriber will "unlock" the row, no need to do it here

            pcntl_signal_dispatch();
            //            $this->log();
            $this->endOk = 6;
            return ;
        }

        // checking ended

        // order to write meta for the subdef if needed

        pcntl_signal_dispatch();
        //        $this->log();

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

        pcntl_signal_dispatch();
        //        $this->log();

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

        pcntl_signal_dispatch();
        //        $this->log();

        $this->indexer->flushQueue();

        $this->messagePublisher->pushLog(sprintf("subdefinition created %s databoxname=%s databoxid=%d recordid=%d",
            $payload['subdefName'], $databox->get_viewname(), $databoxId, $recordId));

        // tell that we have finished to work on this file (=unlock)

        pcntl_signal_dispatch();
        //        $this->log();

        $this->repoWorker->markFinished($this->workerRunningJobId);

        pcntl_signal_dispatch();
        $this->getDataboxLogger($databox)->initOrUpdateLogDocsFromWorker($record, $databox, $workerRunningJob, $subdefName, \Session_Logger::EVENT_SUBDEFCREATION, new \DateTime('now'), WorkerRunningJob::FINISHED);

        $this->endOk = 100;
        $this->log("end_process");
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

        pcntl_signal_dispatch();
        //        $this->log();

        $connection = $record->getDatabox()->get_connection();

        pcntl_signal_dispatch();
        //        $this->log();

        $connection->beginTransaction();

        // mark subdef created
        $sql = 'UPDATE record'
            . ' SET jeton=(jeton & ~(:token)), moddate=NOW()'
            . ' WHERE record_id=:record_id';

        $stmt = $connection->prepare($sql);


        pcntl_signal_dispatch();
        //        $this->log();

        $stmt->execute([
            ':record_id'    => $record->getRecordId(),
            ':token'        => PhraseaTokens::MAKE_SUBDEF,
        ]);


        pcntl_signal_dispatch();
        //        $this->log();

        $connection->commit();

        pcntl_signal_dispatch();
        //        $this->log();

        $stmt->closeCursor();

        pcntl_signal_dispatch();
        //        $this->log();

    }
}
