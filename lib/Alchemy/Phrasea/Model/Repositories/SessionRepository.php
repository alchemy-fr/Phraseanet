<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * SessionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SessionRepository extends EntityRepository
{
    public function deleteAllExceptSessionId($sessionId)
    {
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->neq('id', $sessionId));
        $sessions = $this->matching($criteria);

        foreach ($sessions as $session) {
            $this->_em->remove($session);
        }

        $this->_em->flush();
    }
}
