<?php

namespace App\Repository;

use App\Entity\ApiOauthRefreshToken;
use App\Entity\ApiAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiOauthRefreshToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiOauthRefreshToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiOauthRefreshToken[]    findAll()
 * @method ApiOauthRefreshToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiOauthRefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiOauthRefreshToken::class);
    }

    public function findByAccount(ApiAccount $account)
    {
        $qb = $this->createQueryBuilder('rt');
        $qb->where($qb->expr()->eq('rt.account', ':account'));
        $qb->setParameter(':account', $account);

        return $qb->getQuery()->getResult();
    }
}
