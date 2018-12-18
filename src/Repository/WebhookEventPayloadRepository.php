<?php

namespace App\Repository;

use App\Entity\WebhookEventPayload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method WebhookEventPayload|null find($id, $lockMode = null, $lockVersion = null)
 * @method WebhookEventPayload|null findOneBy(array $criteria, array $orderBy = null)
 * @method WebhookEventPayload[]    findAll()
 * @method WebhookEventPayload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebhookEventPayloadRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WebhookEventPayload::class);
    }

    public function save(WebhookEventPayload $payload)
    {
        $this->_em->persist($payload);
        $this->_em->persist($payload->getDelivery());

        $this->_em->flush([ $payload, $payload->getDelivery() ]);
    }
}
