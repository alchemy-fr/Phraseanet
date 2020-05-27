<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\WorkerRunningPopulate;
use Doctrine\ORM\EntityRepository;

class WorkerRunningPopulateRepository extends EntityRepository
{
    public function getEntityManager()
    {
        return parent::getEntityManager();
    }

    /**
     * @param array $databoxIds
     * @return int
     */
    public function checkPopulateStatusByDataboxIds(array $databoxIds)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->where($qb->expr()->in('w.databoxId', $databoxIds))
            ->andWhere('w.status = :status')
            ->setParameter('status', WorkerRunningPopulate::RUNNING)
        ;

        return count($qb->getQuery()->getResult());
    }
}
