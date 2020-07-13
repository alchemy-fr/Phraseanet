<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Doctrine\ORM\EntityRepository;


class WorkerJobRepository extends EntityRepository
{
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
