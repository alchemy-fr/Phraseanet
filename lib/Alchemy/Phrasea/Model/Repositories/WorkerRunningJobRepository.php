<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Exception;

class WorkerRunningJobRepository extends EntityRepository
{
    /**
     * Check and declare that we want to create a subdef from a document
     *
     * - if it's possible : return WorkerRunningJob entity (created or updated) for the job
     * - if not (needed resource(s) already in use by other job(s)) : return null
     *
     * rules :
     * - if someone else is already writing the document, we can't create a subdef from it
     * - if someone else is already writing this subdef, we can't re-create it
     *
     * @param array $payload
     * @return WorkerRunningJob | null
     * @throws OptimisticLockException
     */
    public function canCreateSubdef(array $payload)
    {
        $databoxId      = $payload['databoxId'];
        $recordId       = $payload['recordId'];
        $subdefName     = $payload['subdefName'];

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
            sprintf('canCreateSubdef for %s.%s.%s ?', $databoxId, $recordId, $subdefName)
        ), FILE_APPEND|LOCK_EX);

        // first protect sql by a critical section
        if( !( $recordMutex = $this->getRecordMutex($databoxId, $recordId)) ) {
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                'getRecordMutex() failed'
            ), FILE_APPEND|LOCK_EX);

            return null;
        }

        // here we can do sql atomically
        $workerRunningJob = null;

        // check the rules
        /** @var WorkerRunningJob $r */
        $r = $this->createQueryBuilder('w')
            ->select('w')
            ->where('w.status = :status')->setParameter('status', WorkerRunningJob::RUNNING)
            ->andWhere('w.databoxId = :databox_id')->setParameter('databox_id', $databoxId)
            ->andWhere('w.recordId = :record_id')->setParameter('record_id', $recordId)
            ->andWhere('w.workOn = \'document\' OR w.work_on = :work_on')->setParameter(':work_on', $subdefName)
            ->andWhere('w.work = :work_1 OR w.work = :work_2')
            ->setParameter('work_1', MessagePublisher::WRITE_METADATAS_TYPE)
            ->setParameter('work_2', MessagePublisher::SUBDEF_CREATION_TYPE)
            ->setMaxResults(1)
            ->getQuery()
            ->getFirstResult()
        ;

        if(!$r) {
            // no conflict, create (or update) the job
            $workerRunningJob = $this->creteOrUpdateJob($payload, MessagePublisher::SUBDEF_CREATION_TYPE);
        }
        else {
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                sprintf("job %s already running on %s.%s.%s", $r->getId(), $r->getDataboxId(), $r->getRecordId(), $r->getWorkOn())
            ), FILE_APPEND|LOCK_EX);
        }

        // end of critical section
        $this->releaseRecordMutex($recordMutex);

        return $workerRunningJob;
    }

    /**
     * Check and declare that we want to write meta into a subdef
     *
     * - if it's possible : return WorkerRunningJob entity (created or updated) for the job
     * - if not (needed resource(s) already in use by other job(s)) : return null
     *
     * rule :
     * - if someone is already working on the file, we can't write
     *
     * @param array $payload
     * @return WorkerRunningJob | null
     * @throws OptimisticLockException
     */
    public function canWriteMetadata(array $payload)
    {
        $databoxId      = $payload['databoxId'];
        $recordId       = $payload['recordId'];
        $subdefName     = $payload['subdefName'];

        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
            sprintf('canWriteMetadata for %s.%s.%s ?', $databoxId, $recordId, $subdefName)
        ), FILE_APPEND|LOCK_EX);

        // first protect sql by a critical section
        if( !( $recordMutex = $this->getRecordMutex($databoxId, $recordId)) ) {
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                'getRecordMutex() failed'
            ), FILE_APPEND|LOCK_EX);

            return null;
        }

        // here we can do sql atomically
        $workerRunningJob = null;

        // check the rule
        $r = $this->createQueryBuilder('w')
            ->select('w')
            ->where('w.status = :status')->setParameter('status', WorkerRunningJob::RUNNING)
            ->andWhere('w.databox_id = :databox_id')->setParameter('databox_id', $databoxId)
            ->andWhere('w.record_id = :record_id')->setParameter('record_id', $recordId)
            ->andWhere('w.work_on = :work_on')->setParameter(':work_on', $subdefName)
            ->andWhere('w.work = :work_1 OR w.work = :work_2')
            ->setParameter('work_1', MessagePublisher::WRITE_METADATAS_TYPE)
            ->setParameter('work_2', MessagePublisher::SUBDEF_CREATION_TYPE)
            ->setMaxResults(1)
            ->getQuery()
            ->getFirstResult()
        ;

        if(!$r) {
            // no conflict, create (or update) the job
            $workerRunningJob = $this->creteOrUpdateJob($payload, MessagePublisher::WRITE_METADATAS_TYPE);
        }
        else {
            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                sprintf("job %s already running on %s.%s.%s", $r->getId(), $r->getDataboxId(), $r->getRecordId(), $r->getWorkOn())
            ), FILE_APPEND|LOCK_EX);

        }

        // end of critical section
        $this->releaseRecordMutex($recordMutex);

        return $workerRunningJob;
    }

    /**
     * @param array $payload
     * @param string $type
     * @return WorkerRunningJob|null
     * @throws OptimisticLockException
     */
    private function creteOrUpdateJob(array $payload, string $type)
    {
        // for unpredicted sql errors we can still ignore and return null (lock failed),
        // because anyway the worker/rabbit retry system will stop itself after n failures.

        if (!isset($payload['workerJobId'])) {

            // new job

            $this->getEntityManager()->beginTransaction();
            try {
                $date = new DateTime();
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setDataboxId($payload['databoxId'])
                    ->setRecordId($payload['recordId'])
                    ->setWork($type)
                    ->setWorkOn($payload['subdefName'])
                    ->setPayload([
                        'message_type'  => $type,
                        'payload'       => $payload
                    ])
                    ->setPublished($date->setTimestamp($payload['published']))
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $this->getEntityManager()->persist($workerRunningJob);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                    sprintf("created job %s for %s.%s.%s", $type, $payload['databoxId'], $payload['recordId'], $payload['subdefName'])
                ), FILE_APPEND|LOCK_EX);

            }
            catch (Exception $e) {
                // bad case : we return false anyway
                $this->getEntityManager()->rollback();
                // $this->logger->error("Error persisting WorkerRunningJob !");
                $workerRunningJob = null;

                file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                    sprintf("!!! error creating job %s for %s.%s.%s", $type, $payload['databoxId'], $payload['recordId'], $payload['subdefName'])
                ), FILE_APPEND|LOCK_EX);

            }
        }
        else {

            // retry from delayed

            /** @var WorkerRunningJob $workerRunningJob */
            if(!is_null($workerRunningJob = $this->find($payload['workerJobId']))) {
                // update retry count (value is already incremented in payload)
                $workerRunningJob
                    ->setInfo(WorkerRunningJob::ATTEMPT . $payload['count'])
                    ->setStatus(WorkerRunningJob::RUNNING);

                $this->getEntityManager()->persist($workerRunningJob);
                $this->getEntityManager()->flush();

                file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                    sprintf("incremented job %s for %s.%s.%s (count=%s)", $type, $payload['databoxId'], $payload['recordId'], $payload['subdefName'], $payload['count'])
                ), FILE_APPEND|LOCK_EX);

            }
            else {
                // the row has been deleted by purge ?
                // bad case : we return false anyway

                // $this->logger->error("Given workerJobId not found !");
                file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                    sprintf("!!! error incrementing job %s for %s.%s.%s (count=%s)", $type, $payload['databoxId'], $payload['recordId'], $payload['subdefName'], $payload['count'])
                ), FILE_APPEND|LOCK_EX);

            }
        }

        return $workerRunningJob;
    }

    /**
     * Acquire a "mutex" to protect critical section on a (sbid + rid) by trying to insert a row in WorkerRunningJob table.
     * If it fails that means that another critical section is already running on this record.
     *
     * @param int $databoxId
     * @param int $recordId
     * @return WorkerRunningJob|null      // the created mutex entity, or null if mutex already exists
     */
    private function getRecordMutex(int $databoxId, int $recordId)
    {
        try {
            $this->getEntityManager()->beginTransaction();
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setDataboxId($databoxId)
                ->setRecordId($recordId)
                ->setPublished(new DateTime())
                ->setStatus('')
                ->setFlock("_mutex_");
            $this->getEntityManager()->persist($workerRunningJob);

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                sprintf("getMutex for %s.%s OK (%s)", $databoxId, $recordId, $workerRunningJob->getId())
            ), FILE_APPEND|LOCK_EX);

            return $workerRunningJob;
        }
        catch(Exception $e) {
            // duplicate key ?
            $this->getEntityManager()->rollback();

            file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                sprintf("getMutex for %s.%s FAILED", $databoxId, $recordId)
            ), FILE_APPEND|LOCK_EX);

            return null;
        }
    }

    private function releaseRecordMutex(WorkerRunningJob $workerRunningJob)
    {
        file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
            sprintf("releaseMutex (%s) for %s.%s", $workerRunningJob->getId(), $workerRunningJob->getDataboxId(), $workerRunningJob->getRecordId())
        ), FILE_APPEND|LOCK_EX);

        $this->getEntityManager()->remove($workerRunningJob);
    }



    /**
     * mark a job a "finished"
     * nb : after a long job, connection may be lost so we reconnect.
     *      But sometimes (?) a first commit fails (due to reconnect ?), while the second one is ok.
     *      So here we try 2 times, just in case...
     *
     * @param WorkerRunningJob $workerRunningJob
     * @param null $info
     */
    public function markFinished(WorkerRunningJob $workerRunningJob, $info = null)
    {
        $this->reconnect();
        for($try=1; $try<=2; $try++) {
            try {
                $workerRunningJob->setStatus(WorkerRunningJob::FINISHED)
                    ->setFinished(new DateTime('now'))
                    ->setStatus(WorkerRunningJob::FINISHED);
                if (!is_null($info)) {
                    $workerRunningJob->setInfo($info);
                }

                $this->getEntityManager()->beginTransaction();
                $this->getEntityManager()->persist($workerRunningJob);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                    sprintf("job %s (%d) finished for %s.%s.%s", $workerRunningJob->getWork(), $workerRunningJob->getId(), $workerRunningJob->getDataboxId(), $workerRunningJob->getRecordId(), $workerRunningJob->getWorkOn())
                ), FILE_APPEND|LOCK_EX);

                break;
            }
            catch (Exception $e) {
                file_put_contents(dirname(__FILE__).'/../../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (\DateTime::createFromFormat('U.u', microtime(TRUE)))->format('Y-m-d\TH:i:s.u'), getmypid(), __FILE__, __LINE__,
                    sprintf("!!! failed to mark job %s (%d) as finished (try %s/2) for %s.%s.%s", $workerRunningJob->getWork(), $workerRunningJob->getId(), $try, $workerRunningJob->getDataboxId(), $workerRunningJob->getRecordId(), $workerRunningJob->getWorkOn())
                ), FILE_APPEND|LOCK_EX);

                $this->getEntityManager()->rollback();
            }
        }
    }

    /**
     * @param array $databoxIds
     * @return int
     */
    public function checkPopulateStatusByDataboxIds(array $databoxIds)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->where($qb->expr()->in('w.databoxId', $databoxIds))
            ->andWhere('w.work = :work')
            ->andWhere('w.status = :status')
            ->setParameters([ 'work' => MessagePublisher::POPULATE_INDEX_TYPE, 'status' => WorkerRunningJob::RUNNING])
        ;

        return count($qb->getQuery()->getResult());
    }

    public function findByStatus(array $status, $start = 0, $limit = WorkerRunningJob::MAX_RESULT)
    {
        $qb = $this->createQueryBuilder('w');
        $qb
            ->where($qb->expr()->in('w.status', $status))
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->orderBy('w.id', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $commitId
     * @return bool
     */
    public function canAckUploader($commitId)
    {
        $qb = $this->createQueryBuilder('w');
        $res = $qb
            ->where('w.commitId = :commitId')
            ->andWhere('w.work = :work')
            ->andWhere('w.status != :status')
            ->setParameters([
                'commitId' => $commitId,
                'work'     => MessagePublisher::ASSETS_INGEST_TYPE,
                'status'   => WorkerRunningJob::FINISHED
            ])
            ->getQuery()
            ->getResult()
        ;

        return count($res) == 0;
    }

    public function truncateWorkerTable()
    {
        $connection = $this->_em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $this->_em->beginTransaction();
        try {
            $connection->executeUpdate($platform->getTruncateTableSQL('WorkerRunningJob'));
        }
        catch (Exception $e) {
            $this->_em->rollback();
        }
    }

    public function deleteFinishedWorks()
    {
        $this->_em->beginTransaction();
        try {
            $this->_em->getConnection()->delete('WorkerRunningJob', ['status' => WorkerRunningJob::FINISHED]);
            $this->_em->commit();
        }
        catch (Exception $e) {
            $this->_em->rollback();
        }
    }

    public function getEntityManager()
    {
        return parent::getEntityManager();
    }

    public function reconnect()
    {
        if($this->_em->getConnection()->ping() === false) {
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }
}
