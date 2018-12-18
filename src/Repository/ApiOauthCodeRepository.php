<?php

namespace App\Repository;

use App\Entity\ApiOauthCode;
use App\Entity\ApiAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiOauthCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiOauthCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiOauthCode[]    findAll()
 * @method ApiOauthCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiOauthCodeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiOauthCode::class);
    }

    public function findByAccount(ApiAccount $account)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->eq('c.account', ':account'));
        $qb->setParameter(':account', $account);

        return $qb->getQuery()->getResult();
    }
}
