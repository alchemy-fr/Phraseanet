<?php

namespace App\Repository;

use App\Entity\UserQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UserQuery|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserQuery|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserQuery[]    findAll()
 * @method UserQuery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserQueryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserQuery::class);
    }

    // /**
    //  * @return UserQuery[] Returns an array of UserQuery objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserQuery
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
