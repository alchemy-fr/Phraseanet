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
     * Acquire a "lock" to create a subdef
     * @param array $payload
     * @return WorkerRunningJob
     * @throws OptimisticLockException
     */
    public function canCreateSubdef($payload)
    {
        return $this->getLock($payload, MessagePublisher::SUBDEF_CREATION_TYPE);
    }

    /**
     * Acquire a "lock" to write meta into a subdef
     * @param array $payload
     * @return WorkerRunningJob
     * @throws OptimisticLockException
     */
    public function canWriteMetadata($payload)
    {
        return $this->getLock($payload, MessagePublisher::WRITE_METADATAS_TYPE);
    }

    /**
     * Acquire a "lock" to work on a (sbid + rid + subdef) by inserting a row in WorkerRunningJob table.
     * If it fails that means that another worker is already working on this file.
     *
     * nb : this work only for "first try" where workerJobId is null (=insert).
     *      for some retries (lock was acquired but worker job failed), the "count" of existing row is incremented (=update),
     *      so many workers "could" update the same row...
     *      __Luckily__, a rabbitmq message is consumed only once by a unique worker,
     *      and different workers (write-meta, subdef) have their own queues and their own rows on table.
     *      So working on a file always starts by a "first try", and concurency is not possible.
     * todo : do not update, but insert a line for every try ?
     *
     * @param array $payload
     * @param string $type
     * @return WorkerRunningJob      the entity (created or updated) or null if file is already locked (duplicate key)
     * @throws OptimisticLockException
     */
    private function getLock(array $payload, string $type)
    {
        if(!isset($payload['workerJobId'])) {
            // insert a new row WorkerRunningJob : will fail if concurency
            try {
                $this->getEntityManager()->beginTransaction();
                $date = new DateTime();
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setDataboxId($payload['databoxId'])
                    ->setRecordId($payload['recordId'])
                    ->setWork($type)
                    ->setWorkOn($payload['subdefName'])
                    ->setPayload([
                        'message_type' => $type,
                        'payload'      => $payload
                    ])
                    ->setPublished($date->setTimestamp($payload['published']))
                    ->setStatus(WorkerRunningJob::RUNNING)
                    ->setFlock($payload['subdefName']);
                $this->getEntityManager()->persist($workerRunningJob);

                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                return $workerRunningJob;
            }
            catch(Exception $e) {
                // duplicate key ?
                $this->getEntityManager()->rollback();
                // for unpredicted other errors we can still ignore and return null (lock failed),
                // because anyway the worker/rabbit retry-system will stop itself after n failures.
            }
        }
        else {
            // update an existing row : never fails (except bad id if row was purged)
            try {
                $this->getEntityManager()->beginTransaction();
                $this->getEntityManager()->createQueryBuilder()
                    ->update()
                    ->set('info', ':info')->setParameter('info', WorkerRunningJob::ATTEMPT . $payload['count'])
                    ->set('status', ':status')->setParameter('status', WorkerRunningJob::RUNNING)
                    ->set('flock', ':flock')->setParameter('flock', $payload['subdefName'])
                    ->where('id = :id')->setParameter('id', $payload['workerJobId'])
                    ->getQuery()
                    ->execute();

                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                return $this->find($payload['workerJobId']);
            }
            catch (Exception $e) {
                // really bad ? return null anyway
                $this->getEntityManager()->rollback();
                //$this->logger->error("Error persisting WorkerRunningJob !");
            }
        }

        return null;
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
                    ->setStatus(WorkerRunningJob::FINISHED)
                    ->setFlock(null);
                if (!is_null($info)) {
                    $workerRunningJob->setInfo($info);
                }

                $this->getEntityManager()->beginTransaction();
                $this->getEntityManager()->persist($workerRunningJob);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                break;
            }
            catch (Exception $e) {
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
