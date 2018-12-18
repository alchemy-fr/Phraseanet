<?php

namespace App\Repository;

use App\Entity\ApiOauthToken;
use App\Entity\ApiAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiOauthToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiOauthToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiOauthToken[]    findAll()
 * @method ApiOauthToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiOauthTokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiOauthToken::class);
    }

    /**
     * @param ApiAccount $account
     * @return ApiOauthToken|null
     */
    public function findDeveloperToken(ApiAccount $account)
    {
        $qb = $this->createQueryBuilder('tok');
        $qb->innerJoin('tok.account', 'acc', Expr\Join::WITH, $qb->expr()->eq('acc.id', ':acc_id'));
        $qb->innerJoin('acc.application', 'app', Expr\Join::WITH, $qb->expr()->orx(
            $qb->expr()->eq('app.creator', 'acc.user'),
            $qb->expr()->isNull('app.creator')
        ));
        $qb->where($qb->expr()->isNull('tok.expires'));
        $qb->orderBy('tok.created', 'DESC');
        $qb->setParameter(':acc_id', $account->getId());

        /*
         * @note until we add expiration token, there is no way to distinguish a developer issued token from
         * a connection process issued token.
         */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param ApiAccount $account
     * @return ApiOauthToken[]
     */
    public function findOauthTokens(ApiAccount $account)
    {
        $qb = $this->createQueryBuilder('tok');
        $qb->where($qb->expr()->eq('tok.account', ':acc'));
        $qb->setParameter(':acc', $account);

        return $qb->getQuery()->getResult();
    }
}
