<?php

namespace App\Repository;

use App\Entity\FtpCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FtpCredential|null find($id, $lockMode = null, $lockVersion = null)
 * @method FtpCredential|null findOneBy(array $criteria, array $orderBy = null)
 * @method FtpCredential[]    findAll()
 * @method FtpCredential[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FtpCredentialRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FtpCredential::class);
    }

    // /**
    //  * @return FtpCredential[] Returns an array of FtpCredential objects
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
    public function findOneBySomeField($value): ?FtpCredential
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
