<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\WorkerRunningUploader;
use Doctrine\ORM\EntityRepository;

class WorkerRunningUploaderRepository extends EntityRepository
{
    public function getEntityManager()
    {
        return parent::getEntityManager();
    }

    /**
     * @param $commitId
     * @return bool
     */
    public function canAck($commitId)
    {
        $qb = $this->createQueryBuilder('w');
        $res = $qb
                ->where('w.commitId = :commitId')
                ->andWhere('w.status != :status')
                ->setParameters([
                    'commitId' => $commitId,
                    'status'   => WorkerRunningUploader::DOWNLOADED
                ])
                ->getQuery()
                ->getResult()
                ;

        return count($res) == 0;
    }
}
