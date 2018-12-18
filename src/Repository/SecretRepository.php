<?php

namespace App\Repository;

use App\Entity\Secret;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Secret|null find($id, $lockMode = null, $lockVersion = null)
 * @method Secret|null findOneBy(array $criteria, array $orderBy = null)
 * @method Secret[]    findAll()
 * @method Secret[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecretRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Secret::class);
    }

    public function save(Secret $secret)
    {
        $this->_em->persist($secret);
        $this->_em->flush($secret);
    }
}
