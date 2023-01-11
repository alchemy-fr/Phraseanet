<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use DateTime;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Exception;
use PDO;

class WorkerRunningJobRepository extends EntityRepository
{

    /**
     * Check and declare that we want to create a subdef from a document
     *
     * - if it's possible : return WorkerRunningJobId (created or updated) for the job
     * - if not (needed resource(s) already in use by other job(s)) : return null
     *
     * rules :
     * - if someone else is already writing the document, we can't create a subdef from it
     * - if someone else is already writing this subdef, we can't re-create it
     *
     * @param array $payload
     * @return int | null           workerRunningJobId
     */
    public function canCreateSubdef(array $payload)
    {
        $this->reconnect();
        $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();

        $sqlclause = "(`work_on` = " . $cnx->quote('document') . " OR `work_on` = " . $cnx->quote($payload['subdefName']) . ")";

        return $this->canDoJob($payload, MessagePublisher::SUBDEF_CREATION_TYPE, $sqlclause);
    }


    /**
     * Check and declare that we want to write meta into a subdef
     *
     * - if it's possible : return WorkerRunningJobId (created or updated) for the job
     * - if not (needed resource(s) already in use by other job(s)) : return null
     *
     * rule :
     * - if someone is already working on the file, we can't write
     * - if someone is building subdefs, we can't write on tne document
     *
     * @param array $payload
     * @return int | null       workerRunningJobId
     */
    public function canWriteMetadata(array $payload)
    {
        $this->reconnect();
        $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();

        // if someone is already working on the file, we can't write
        $sqlclause = "(`work_on` = " . $cnx->quote($payload['subdefName']) . ")";

        if($payload['subdefName'] === "document") {
            // if someone is building subdefs, we can't write on tne document
            $sqlclause = "(" . $sqlclause . " OR (`work` = " . $cnx->quote(MessagePublisher::SUBDEF_CREATION_TYPE) . "))";
        }

        return $this->canDoJob($payload, MessagePublisher::WRITE_METADATAS_TYPE, $sqlclause);
    }

    /**
     * Check and declare that we want to do jon for a subdef
     *
     * - if it's possible : return WorkerRunningJobId (created or updated) for the job
     * - if not (needed resource(s) already in use by other job(s)) : return null
     *
     * The rule depends on caller / jobType (canCreateSubdef or canWriteMetadata)
     *
     * @param array $payload
     * @param string $jobType           // MessagePublisher::WRITE_METADATAS_TYPE | MessagePublisher::SUBDEF_CREATION_TYPE
     * @param string $sqlClause
     * @return int | null       workerRunningJobId
     */
    private function canDoJob(array $payload, string $jobType, string $sqlClause)
    {
        $workerRunningJobId = null;     // returned

        $databoxId      = $payload['databoxId'];
        $recordId       = $payload['recordId'];

        // first protect sql by a critical section
        if( !( $recordMutexId = $this->getRecordMutex($databoxId, $recordId)) ) {

            return null;
        }

        // here we can do sql atomically

        $this->reconnect();
        $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();

        if($cnx->beginTransaction() === true) {

            try {
                $row = null;
                $sql = "SELECT * FROM `WorkerRunningJob` WHERE\n"
                    . " `status` = " . $cnx->quote(WorkerRunningJob::RUNNING) . " AND\n"
                    . " `databox_id` = " . $cnx->quote($databoxId, PDO::PARAM_INT) . " AND\n"
                    . " `record_id` = " . $cnx->quote($recordId, PDO::PARAM_INT) . " AND\n"
                    . " " . $sqlClause . " AND\n"
                    . " (`work` = " . $cnx->quote(MessagePublisher::WRITE_METADATAS_TYPE) . " OR `work` = " . $cnx->quote(MessagePublisher::SUBDEF_CREATION_TYPE) . ")\n"
                    . " LIMIT 1";

                $stmt = $cnx->prepare($sql);
                if ($stmt->execute() === true) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                $stmt->closeCursor();

                if(!$row) {
                    // no job running : create or update (may return false) if error
                    $workerRunningJobId = $this->creteOrUpdateJob($cnx, $payload, $jobType);
                }

                $cnx->commit();
            }
            catch (Exception $e) {
                $cnx->rollBack();
            }
        }

        // end of critical section
        $this->releaseMutex($recordMutexId);

        return $workerRunningJobId;
    }

    /**
     * @param Connection $cnx
     * @param array $payload
     * @param string $type
     * @return int|null     // workerJobId
     */
    private function creteOrUpdateJob(Connection $cnx, array $payload, string $type)
    {
        // for unpredicted sql errors we can still ignore and return null (lock failed),
        // because anyway the worker/rabbit retry system will stop itself after n failures.

        $workerJobId = null;

        try {
            if (!isset($payload['workerJobId'])) {

                // new job

                $datePublished = new DateTime();
                $datePublished->setTimestamp($payload['published']);
                $datePublished->format('Y-m-d H:i:s');

                $pl = json_encode([
                    'message_type' => $type,
                    'payload'      => $payload
                ]);

                $sql = "INSERT INTO `WorkerRunningJob` SET \n"
                    . " `databox_id` = " . $cnx->quote($payload['databoxId'], PDO::PARAM_INT) . ",\n"
                    . " `record_id` = " . $cnx->quote($payload['recordId'], PDO::PARAM_INT) . ",\n"
                    . " `work` = " . $cnx->quote($type) . ",\n"
                    . " `work_on` = " . $cnx->quote($payload['subdefName']) . ",\n"
                    . " `payload` = " . $cnx->quote($pl) . ",\n"
                    . " `created` = NOW(),\n"
                    . " `published` = " . $cnx->quote($datePublished->format('Y-m-d H:i:s')) . ",\n"
                    . " `status` = " . $cnx->quote(WorkerRunningJob::RUNNING);

                if ($cnx->exec($sql) === 1) {
                    // went well, the row is inserted
                    $workerJobId = $cnx->lastInsertId();
                }
                else {
                    // row not inserted ?
                    throw new Exception("Failed to insert into WorkerRunningJob");
                }
            }
            else {
                // retry from delayed : update retry count (value is already incremented in payload)
                $sql = "UPDATE `WorkerRunningJob` SET \n"
                    . " `info` = " . $cnx->quote(WorkerRunningJob::ATTEMPT . $payload['count']) . ",\n"
                    . " `status` = " . $cnx->quote(WorkerRunningJob::RUNNING)
                    . " WHERE `id` = " . $cnx->quote($payload['workerJobId'], PDO::PARAM_INT);

                if ($cnx->exec($sql) === 1) {
                    // went well, the row is updated
                    $workerJobId = $payload['workerJobId'];
                }
                else {
                    // row not inserted ?
                    throw new Exception(sprintf("Failed to update WorkerRunningJob with id=%s", $payload['workerJobId']));
                }
            }
        }
        catch (Exception $e) {
            // bad case : we return null anyway
        }

        return $workerJobId;
    }

    /**
     * Acquire a "mutex" to protect critical section on a (sbid + rid) by trying to insert a row in WorkerRunningJob table.
     * If it fails that means that another critical section is already running on this record.
     *
     * when many q-messages are consumed at the same time, many process may ask the same mutex immediatly, many fails.
     * so we retry after a short random delay which gives a good chance to ok, and avoids unnecessary "delayed" q-messages.
     *
     *
     * !!! IMPORTANT !!!
     * we CAN'T use the entity manager to insert, because if this fails with exception (possible case),
     * the EM will be closed and we will have no other chance for anothe tryout.
     * So we play plain sql everywhere here.
     *
     * @param int $databoxId
     * @param int $recordId
     * @return bool
     */
    private function getRecordMutex(int $databoxId, int $recordId)
    {
        // First we delete old unreleased mutex (which should never happen).
        // A mutex is supposed to last only for a very short time (select + insert-or-update).
        // 60s is considered as a dead mutex
        //
        try {
            $this->reconnect();
            $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();

            $sql = "DELETE FROM `WorkerRunningJob` WHERE\n"
                . " `databox_id` = " . $cnx->quote($databoxId) . " AND\n"
                . " `record_id` = " . $cnx->quote($recordId) . " AND\n"
                . " `flock` = " . $cnx->quote('_mutex_') . " AND\n"
                . " TIMESTAMPDIFF(SECOND, `published`, NOW()) > 60";

            $cnx->exec($sql);
        }
        catch(Exception $e) {
            // here something went very wrong, like sql death

            return false; // we could choose to continue, but if we end up here... better to stop
        }

        // here we create a mutex, which CAN fail if another process did the same right before us
        //
        $e = null;  // last exception if failed

        for($tryout=1; $tryout<=3; $tryout++) {
            try {
                $this->reconnect();

                $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();

                $sql = "INSERT INTO WorkerRunningJob (`databox_id`, `record_id`, `published`, `status`, `flock`) VALUES (\n"
                    . $cnx->quote($databoxId) . ",\n"
                    . $cnx->quote($recordId) . ",\n"
                    . "NOW(),\n"
                    . $cnx->quote('_') . ",\n"
                    . $cnx->quote('_mutex_') . "\n"
                    . ")";

                if(($a = $cnx->exec($sql)) === 1) {

                    return $cnx->lastInsertId();
                }

                throw new Exception(sprintf("inserting mutex should return 1 row affected, got %s", $a));
            }
            catch (Exception $e) {
                /**
                 * with plain sql, EM should still be opened here
                 */

                // duplicate key is possible, we retry on any kind of error
                if($tryout < 3) {
                    $rnd = rand(10, 50) * 10;   // 100 ms ... 500 ms with 10 ms steps

                    usleep($rnd * 1000);
                }
            }
        }

        return false;
    }

    /**
     * Release a mutex by deleting it.
     * This should not fail, but -as for creation-, we will try N times
     *
     * @param int $recordMutexId
     */
    private function releaseMutex(int $recordMutexId)
    {
        $e = null;  // last exception if failed
        for($tryout=1; $tryout<=3; $tryout++) {
            try {
                $this->reconnect();

                /**
                 * because we did not create an entity for mutex row,
                 * we must use plain sql also to delete it
                 */
                $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();
                $sql = "DELETE FROM WorkerRunningJob WHERE `id` = " . $cnx->quote($recordMutexId);

                $cnx->exec($sql);

                return;
            }
            catch (Exception $e) {
                if($tryout < 3) {
                    $rnd = rand(10, 50) * 10;   // 100 ms ... 500 ms with 10 ms steps

                    usleep($rnd * 1000);
                }
            }
        }

        // Here we were not able to release a mutex (bad)
        // The last chance will be later, when old mutex (60s) is deleted
    }

    /**
     * mark a job a "finished"
     * nb : after a long job, connection may be lost so we reconnect.
     *      But sometimes (?) a first commit fails (due to reconnect ?), while the second one is ok.
     *      So here we try 2 times, just in case...
     *
     * @param int $workerRunningJobId
     * @param null $info
     */
    public function markFinished(int $workerRunningJobId, $info = null)
    {
        for($tryout=1; $tryout<=2; $tryout++) {
            try {
                $this->reconnect();
                $cnx = $this->getEntityManager()->getConnection()->getWrappedConnection();
                $sql = "UPDATE `WorkerRunningJob` SET \n"
                    . " `finished` = NOW(),\n"
                    . " `status` = " . $cnx->quote(WorkerRunningJob::FINISHED);
                if(!is_null($info)) {
                    $sql .= ",\n `info` = " . $cnx->quote($info);
                }
                $sql .= "\n WHERE `id` = " . $cnx->quote($workerRunningJobId, PDO::PARAM_INT);

                if(($a = $cnx->exec($sql) )=== 1) {
                    // ok

                    return;
                }
                // not ok ? retry
                throw new Exception(sprintf("updating WorkerRunningJob should return 1 row affected, got %s", $a));
            }
            catch (Exception $e) {
                if($tryout < 2) {
                    sleep(1);   // retry in 1 sec
                }
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

    public function findByFilter(array $status, $jobType, $databoxId, $recordId, $fieldTimeFilter, $dateTimeFilter = null, $start = 0, $limit = WorkerRunningJob::MAX_RESULT)
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('info', 'info');
        $rsm->addScalarResult('databoxId', 'databoxId');
        $rsm->addScalarResult('recordId', 'recordId');
        $rsm->addScalarResult('work', 'work');
        $rsm->addScalarResult('workOn', 'workOn');
        $rsm->addScalarResult('published', 'published');
        $rsm->addScalarResult('created', 'created');
        $rsm->addScalarResult('finished', 'finished');
        $rsm->addScalarResult('duration', 'duration');
        $rsm->addScalarResult('status', 'status');

        $sql = "SELECT id, info, databox_id as databoxId, record_id as recordId, work, work_on as workOn, published, created, finished, status, \n"
            . "IF(w.finished IS NULL, TIMESTAMPDIFF(SECOND, w.created, NOW()), TIMESTAMPDIFF(SECOND, w.created, w.finished))  as duration \n"
            . "FROM WorkerRunningJob w \n"
            . "WHERE 1";

        $params = [];
        $statusParam = false;
        if (!empty($status)) {
            $sql .= " AND w.status IN (:status)";
            $statusParam = true;
        }

        if (!empty($jobType)) {
            $sql .= " AND w.work = :work";
            $params['work'] = $jobType;
        }

        if (!empty($databoxId)) {
            $sql .= " AND w.databox_id = :databoxId";
            $params['databoxId'] = $databoxId;
        }

        if (!empty($recordId)) {
            $sql .= " AND w.record_id = :recordId";
            $params['recordId'] = $recordId;
        }

        if ($dateTimeFilter instanceof DateTime) {
            // published or created column
            $sql .= " AND w." . $fieldTimeFilter . " >= :dateTimeFilter";
            $params['dateTimeFilter'] = $dateTimeFilter->format('Y-m-d H:i:s');
        }

        if ($fieldTimeFilter != null) {
            $sql .= " ORDER BY w." . $fieldTimeFilter . " DESC";
        } else {
            $sql .= " ORDER BY w.id DESC";
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . $limit;
        }

        $sql .= " OFFSET " . $start;

        $q = $this->_em->createNativeQuery($sql, $rsm);

        if (!empty($params)) {
            $q->setParameters($params);
        }

        if ($statusParam) {
            $q->setParameter('status', $status, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        return $q->getResult();
    }

    public function getJobCount(array $status, $jobType, $databoxId, $recordId)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select('count(w)');

        if (!empty($status)) {
            $qb->where($qb->expr()->in('w.status', $status));
        }

        if (!empty($jobType)) {
            $qb->andWhere('w.work = :work')
                ->setParameter('work', $jobType);
        }

        if (!empty($databoxId)) {
            $qb->andWhere('w.databoxId = :databoxId')
                ->setParameter('databoxId', $databoxId);
        }

        if (!empty($recordId)) {
            $qb->andWhere('w.recordId = :recordId')
                ->setParameter('recordId', $recordId);
        }

        return  $qb->getQuery()->getSingleScalarResult();
    }

    public function updateStatusRunningToCanceledSinceCreated($hour = 0)
    {
        $sql = '
            UPDATE WorkerRunningJob w
            SET w.status = :canceled
            WHERE w.status = :running
            AND (TO_SECONDS(CURRENT_TIMESTAMP()) - TO_SECONDS(w.created)) > :second'
        ;

        $this->_em->getConnection()->executeUpdate($sql, [
            'second'    => $hour * 3600,
            'running'   => 'running',
            'canceled'  => 'canceled'
        ]);
    }

    public function getRunningSinceCreated($hour = 0)
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\WorkerRunningJob', 'w');
        $selectClause = $rsm->generateSelectClause();

        $sql = '
            SELECT ' . $selectClause . '
            FROM WorkerRunningJob w
            WHERE w.status = :running
            AND (TO_SECONDS(CURRENT_TIMESTAMP()) - TO_SECONDS(w.created)) > :second'
        ;

        $q = $this->_em->createNativeQuery($sql, $rsm);
        $q->setParameters([
            'second'    => $hour * 3600,
            'running'   => 'running'
        ]);

        return $q->getResult();
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
        if(!$this->getEntityManager()->isOpen()) {
            $this->_em = $this->_em->create(
                $this->_em->getConnection(),
                $this->_em->getConfiguration(),
                $this->_em->getEventManager()
            );
        }
    }
}
