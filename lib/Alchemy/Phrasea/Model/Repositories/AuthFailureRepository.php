<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\AuthFailure;
use Doctrine\ORM\EntityRepository;

class AuthFailureRepository extends EntityRepository
{
    /**
     * @param string $limit
     * @return AuthFailure[]
     */
    public function findOldFailures($limit = '-2 months')
    {
        $date = new \DateTime($limit);

        $dql = 'SELECT f
                FROM Phraseanet:AuthFailure f
                WHERE f.created < :date';

        $params = ['date' => $date->format('Y-m-d h:i:s')];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     * @param string $username
     * @param string $ip
     * @return AuthFailure[]
     */
    public function findLockedFailuresMatching($username, $ip)
    {
        $dql = 'SELECT f
                FROM Phraseanet:AuthFailure f
                WHERE (f.username = :username OR f.ip = :ip)
                    AND f.locked = true';

        $params = [
            'username' => $username,
            'ip' => $ip,
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }
}
