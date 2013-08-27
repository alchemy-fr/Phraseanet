<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * OrderRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrderRepository extends EntityRepository
{
    /**
     * Returns an array of all the orders, starting at $offsetStart, limited to $perPage
     *
     * @param array $baseIds
     * @param integer $offsetStart
     * @param integer $perPage
     * @param string $sort
     *
     * @return array
     */
    public function listOrders($baseIds, $offsetStart = 0, $perPage = 20, $sort = "created_on")
    {
        $qb = $this
            ->createQueryBuilder('o')
            ->innerJoin('o.elements', 'e');

         if (!empty($baseIds)) {
             $qb->where($qb->expr()->in('e.baseId', $baseIds));
         }

         if ($sort === 'user') {
             $qb->orderBy('o.userId', 'ASC');
         } else if ($sort === 'usage') {
             $qb->orderBy('o.orderUsage', 'ASC');
         } else {
             $qb->orderBy('o.createdOn', 'ASC');
         }

         $qb
             ->setFirstResult((int) $offsetStart)
             ->setMaxResults(max(10, (int) $perPage));

         return $qb->getQuery()->getResult();
    }

    /**
     * Returns the total number of orders from an array of base_id
     *
     * @param array $baseIds
     *
     * @return integer
     */
    public function countTotalOrders(array $baseIds = array())
    {
        $qb = $this
            ->createQueryBuilder('o');
        $qb->select($qb->expr()->countDistinct('o.id'))
            ->innerJoin('o.elements', 'e');

        if (!empty($baseIds)) {
            $qb->where($qb->expr()->in('e.baseId', $baseIds));
        }

        $qb->groupBy('o.id');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
