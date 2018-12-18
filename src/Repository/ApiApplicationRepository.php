<?php

namespace App\Repository;

use App\Entity\ApiApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ApiApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiApplication[]    findAll()
 * @method ApiApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiApplicationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ApiApplication::class);
    }

    /**
     * @param $clientId
     * @return ApiApplication
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByClientId($clientId)
    {
        $qb = $this->createQueryBuilder('app');
        $qb->where($qb->expr()->eq('app.clientId', ':clientId'));
        $qb->setParameter(':clientId', $clientId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByCreator(User $user)
    {
        $qb = $this->createQueryBuilder('app');
        $qb->where($qb->expr()->eq('app.creator', ':creator'));
        $qb->setParameter(':creator', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return ApiApplication[]
     */
    public function findByUser(User $user)
    {
        $qb = $this->createQueryBuilder('app');
        $qb->innerJoin('app.accounts', 'acc', Expr\Join::WITH, $qb->expr()->eq('acc.user', ':user'));
        $qb->setParameter(':user', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return ApiApplication[]
     */
    public function findAuthorizedAppsByUser(User $user)
    {
        $qb = $this->createQueryBuilder('app');
        $qb->innerJoin('app.accounts', 'acc', Expr\Join::WITH, $qb->expr()->eq('acc.user', ':user'));
        $qb->where($qb->expr()->eq('acc.revoked', $qb->expr()->literal(false)));
        $qb->setParameter(':user', $user);

        return $qb->getQuery()->getResult();
    }

    public function findWithDefinedWebhookCallback()
    {
        $qb = $this->createQueryBuilder('app');
        $qb->where($qb->expr()->isNotNull('app.webhookUrl'));

        return $qb->getQuery()->getResult();
    }
}
