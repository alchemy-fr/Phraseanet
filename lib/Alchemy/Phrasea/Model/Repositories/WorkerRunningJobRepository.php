<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Doctrine\ORM\EntityRepository;

class WorkerRunningJobRepository extends EntityRepository
{
    /**
     *  return true if we can create subdef
     * @param $subdefName
     * @param $recordId
     * @param $databoxId
     * @return bool
     */
    public function canCreateSubdef($subdefName, $recordId, $databoxId)
    {
        $rsm = $this->createResultSetMappingBuilder('w');
        $rsm->addScalarResult('work_on','work_on');

        $sql = 'SELECT work_on
            FROM WorkerRunningJob
            WHERE ((work = :write_meta) OR ((work = :make_subdef) AND work_on = :work_on) ) 
            AND record_id = :record_id 
            AND databox_id = :databox_id
            AND status = :status';

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters([
            'write_meta' => MessagePublisher::WRITE_METADATAS_TYPE,
            'make_subdef'=> MessagePublisher::SUBDEF_CREATION_TYPE,
            'work_on'    => $subdefName,
            'record_id'  => $recordId,
            'databox_id' => $databoxId,
            'status'     => WorkerRunningJob::RUNNING
            ]
        );

        return count($query->getResult()) == 0;
    }

    /**
     * return true if we can write meta
     *
     * @param $subdefName
     * @param $recordId
     * @param $databoxId
     * @return bool
     */
    public function canWriteMetadata($subdefName, $recordId, $databoxId)
    {
        $rsm = $this->createResultSetMappingBuilder('w');
        $rsm->addScalarResult('work_on','work_on');

        $sql = 'SELECT work_on
            FROM WorkerRunningJob
            WHERE ((work = :make_subdef) OR ((work = :write_meta) AND work_on = :work_on) ) 
            AND record_id = :record_id 
            AND databox_id = :databox_id
            AND status = :status';

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters([
                'make_subdef'=> MessagePublisher::SUBDEF_CREATION_TYPE,
                'write_meta' => MessagePublisher::WRITE_METADATAS_TYPE,
                'work_on'    => $subdefName,
                'record_id'  => $recordId,
                'databox_id' => $databoxId,
                'status'     => WorkerRunningJob::RUNNING
            ]
        );

        return count($query->getResult()) == 0;
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
        } catch (\Exception $e) {
            $this->_em->rollback();
        }
    }

    public function deleteFinishedWorks()
    {
        $this->_em->beginTransaction();
        try {
            $this->_em->getConnection()->delete('WorkerRunningJob', ['status' => WorkerRunningJob::FINISHED]);
            $this->_em->commit();
        } catch (\Exception $e) {
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
