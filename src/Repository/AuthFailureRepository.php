<?php

namespace App\Repository;

use App\Entity\AuthFailure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AuthFailure|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthFailure|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthFailure[]    findAll()
 * @method AuthFailure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthFailureRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AuthFailure::class);
    }

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
