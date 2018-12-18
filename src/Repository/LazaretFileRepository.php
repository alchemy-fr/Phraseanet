<?php

namespace App\Repository;

use App\Entity\LazaretFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method LazaretFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method LazaretFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method LazaretFile[]    findAll()
 * @method LazaretFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LazaretFileRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LazaretFile::class);
    }

    public function findPerPage(array $base_ids, $offset = 0, $perPage = 10)
    {
        $builder = $this->createQueryBuilder('f');

        if (! empty($base_ids)) {
            $builder->where($builder->expr()->in('f.base_id', $base_ids));
        }

        $builder
            ->orderBy('f.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
        ;

        return new Paginator($builder->getQuery(), true);
    }
}
