<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Core\Database\ConnectionChecker;
use Doctrine\ORM\EntityRepository;


class WorkerJobRepository extends EntityRepository
{
    public function getEntityManager()
    {
        return parent::getEntityManager();
    }

    public function reconnect()
    {
        if(!ConnectionChecker::isAlive($this->_em->getConnection())) {
            $this->_em->getConnection()->close();
            $this->_em->getConnection()->connect();
        }
    }
}
