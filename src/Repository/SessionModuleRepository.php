<?php

namespace App\Repository;

use App\Entity\SessionModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SessionModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method SessionModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method SessionModule[]    findAll()
 * @method SessionModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionModuleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SessionModule::class);
    }

    // /**
    //  * @return SessionModule[] Returns an array of SessionModule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SessionModule
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
