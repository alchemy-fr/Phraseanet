<?php

namespace App\Repository;

use App\Entity\ApiAccount;
use App\Entity\ApiApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiAccount[]    findAll()
 * @method ApiAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiAccountRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiAccount::class);
    }

    /**
     * @param User           $user
     * @param ApiApplication $application
     * @return ApiAccount
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUserAndApplication(User $user, ApiApplication $application)
    {
        $qb = $this->createQueryBuilder('acc');
        $qb->where($qb->expr()->eq('acc.user', ':user'));
        $qb->andWhere($qb->expr()->eq('acc.application', ':app'));
        $qb->setParameter(':user', $user);
        $qb->setParameter(':app', $application);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return ApiAccount[]
     */
    public function findByUser(User $user)
    {
        $qb = $this->createQueryBuilder('acc');
        $qb->where($qb->expr()->eq('acc.user', ':user'));
        $qb->setParameter(':user', $user);

        return $qb->getQuery()->getResult();
    }
}
