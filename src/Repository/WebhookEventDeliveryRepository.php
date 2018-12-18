<?php

namespace App\Repository;

use App\Entity\WebhookEventDelivery;
use App\Entity\ApiApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method WebhookEventDelivery|null find($id, $lockMode = null, $lockVersion = null)
 * @method WebhookEventDelivery|null findOneBy(array $criteria, array $orderBy = null)
 * @method WebhookEventDelivery[]    findAll()
 * @method WebhookEventDelivery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebhookEventDeliveryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WebhookEventDelivery::class);
    }

    /**
     * @return WebhookEventDelivery[]
     */
    public function findUndeliveredEvents()
    {
        $qb = $this->createQueryBuilder('e');

        $qb
            ->where($qb->expr()->eq('e.delivered', $qb->expr()->literal(false)))
            ->andWhere($qb->expr()->lt('e.deliveryTries', ':nb_tries'));

        $qb->setParameter(':nb_tries', WebhookEventDelivery::MAX_DELIVERY_TRIES);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ApiApplication $apiApplication
     * @param int $count
     * @return WebhookEventDelivery[]
     */
    public function findLastDeliveries(ApiApplication $apiApplication, $count = 10)
    {
        $qb = $this->createQueryBuilder('e');

        $qb
            ->where('e.application = :app')
            ->setMaxResults(max(0, (int) $count))
            ->orderBy('e.created', 'DESC')
            ->setParameters([ 'app' => $apiApplication ]);

        return $qb->getQuery()->getResult();
    }
}
