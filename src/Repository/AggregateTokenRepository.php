<?php

namespace App\Repository;

use App\Entity\AggregateToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AggregateToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method AggregateToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method AggregateToken[]    findAll()
 * @method AggregateToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AggregateTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AggregateToken::class);
    }

    // /**
    //  * @return AggregateToken[] Returns an array of AggregateToken objects
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
    public function findOneBySomeField($value): ?AggregateToken
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
