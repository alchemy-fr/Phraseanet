<?php

namespace App\Repository;

use App\Entity\FtpExportElement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FtpExportElement|null find($id, $lockMode = null, $lockVersion = null)
 * @method FtpExportElement|null findOneBy(array $criteria, array $orderBy = null)
 * @method FtpExportElement[]    findAll()
 * @method FtpExportElement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FtpExportElementRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FtpExportElement::class);
    }

    // /**
    //  * @return FtpExportElement[] Returns an array of FtpExportElement objects
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
    public function findOneBySomeField($value): ?FtpExportElement
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
