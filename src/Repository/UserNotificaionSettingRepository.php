<?php

namespace App\Repository;

use App\Entity\UserNotificationSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UserNotificationSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserNotificationSetting|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserNotificationSetting[]    findAll()
 * @method UserNotificationSetting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserNotificaionSettingRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserNotificationSetting::class);
    }

    // /**
    //  * @return UserNotificationSetting[] Returns an array of UserNotificationSetting objects
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
    public function findOneBySomeField($value): ?UserNotificationSetting
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
