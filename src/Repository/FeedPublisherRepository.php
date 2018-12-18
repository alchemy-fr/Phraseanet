<?php

namespace App\Repository;

use App\Entity\FeedPublisher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FeedPublisher|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedPublisher|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedPublisher[]    findAll()
 * @method FeedPublisher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedPublisherRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FeedPublisher::class);
    }

    // /**
    //  * @return FeedPublisher[] Returns an array of FeedPublisher objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FeedPublisher
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
