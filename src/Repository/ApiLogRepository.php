<?php

namespace App\Repository;

use App\Entity\ApiLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiLog[]    findAll()
 * @method ApiLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiLogRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiLog::class);
    }

    // /**
    //  * @return ApiLog[] Returns an array of ApiLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ApiLog
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
