<?php

namespace App\Repository;

use App\Entity\FeedEntry;
use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FeedEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeedEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeedEntry[]    findAll()
 * @method FeedEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FeedEntry::class);
    }

    /**
     * Returns a collection of FeedEntry from given feeds, limited to $how_many results, starting with $offset_start
     *
     * @param array   $feeds
     * @param integer $offset_start
     * @param integer $how_many
     *
     * @return FeedEntry[]
     */
    public function findByFeeds($feeds, $offset_start = null, $how_many = null)
    {
        $dql = 'SELECT f FROM Phraseanet:FeedEntry f
                WHERE f.feed IN (:feeds) order by f.updatedOn DESC';

        $query = $this->_em->createQuery($dql);
        $query->setParameter('feeds', $feeds);

        if (null !== $offset_start && 0 !== $offset_start) {
            $query->setFirstResult($offset_start);
        }
        if (null !== $how_many) {
            $query->setMaxResults($how_many);
        }

        return $query->getResult();
    }

    /**
     * @param Feed[]|array $feeds List of feeds instance or feed ids to
     * @return int
     */
    public function countByFeeds($feeds)
    {
        $builder = $this->createQueryBuilder('fe');
        $builder
            ->select($builder->expr()->count('fe'))
            ->where($builder->expr()->in('fe.feed', ':feeds'))
            ->setParameter('feeds', $feeds);

        return $builder->getQuery()->getSingleScalarResult();
    }
}
