<?php

namespace App\Repository;

use App\Entity\Feed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Feed|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feed|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feed[]    findAll()
 * @method Feed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Feed::class);
    }

    /**
     * Returns all the feeds a user can access.
     *
     * @param \ACL  $userACL
     * @param array $restrictions
     *
     * @return Feed[]
     */
    public function getAllForUser(\ACL $userACL, array $restrictions = [])
    {
        $base_ids = array_keys($userACL->get_granted_base());

        $qb = $this
            ->createQueryBuilder('f');

        $qb->where($qb->expr()->isNull('f.baseId'))
            ->orWhere($qb->expr()->eq('f.public', $qb->expr()->literal(true)));

        if (count($restrictions) > 0 && count($base_ids) > 0) {
            $base_ids = array_intersect($base_ids, $restrictions);
        }

        if (empty($base_ids) && count($restrictions) > 0) {
            $base_ids = $restrictions;
        }

        if (count($base_ids) > 0) {
            $qb->orWhere($qb->expr()->in('f.baseId', $base_ids));
        }

        $qb->orderBy('f.updatedOn', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all the feeds from a given array containing their id.
     *
     * @param  array      $feedIds
     * @return Feed[]
     */
    public function findByIds(array $feedIds)
    {
        $qb = $this->createQueryBuilder('f');

        if (!empty($feedIds)) {
            $qb->Where($qb->expr()->in('f.id', $feedIds));
        }

        $qb->orderBy('f.updatedOn', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all the feeds from a given array containing their id.
     *
     * @param \ACL   $userACL
     * @param  array $feedIds Ids to restrict feeds, all accessible otherwise
     *
     * @return Feed[]
     */
    public function filterUserAccessibleByIds(\ACL $userACL, array $feedIds = [])
    {
        $qb = $this->createQueryBuilder('f');

        // is public feed?
        $orx = $qb->expr()->orX(
            $qb->expr()->isNull('f.baseId'),
            $qb->expr()->eq('f.public', $qb->expr()->literal(true))
        );

        // is granted base?
        $grantedBases = array_keys($userACL->get_granted_base());
        if ($grantedBases) {
            $orx->add($qb->expr()->in('f.baseId', $grantedBases));
        }

        if ($feedIds) {
            $qb->where($qb->expr()->in('f.id', $feedIds), $orx);
        }

        $qb->orderBy('f.updatedOn', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
