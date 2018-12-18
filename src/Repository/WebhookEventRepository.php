<?php

namespace App\Repository;

use App\Entity\WebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method WebhookEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method WebhookEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method WebhookEvent[]    findAll()
 * @method WebhookEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WebhookEvent::class);
    }

    /**
     * @deprecated This method can overflow available memory when there is a large number of unprocessed events
     */
    public function findUnprocessedEvents()
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where($qb->expr()->eq('e.processed', $qb->expr()->literal(false)));

        return $qb->getQuery()->getResult();
    }

    public function getUnprocessedEventIterator()
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->where('e.processed = 0');

        return $queryBuilder->getQuery()->iterate();
    }
}
