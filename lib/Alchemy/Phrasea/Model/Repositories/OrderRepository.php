<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\ORM\EntityRepository;

class OrderRepository extends EntityRepository
{
    /**
     * Returns the orders initiated by a given user.
     *
     * @param User $user
     *
     * @return Order[]
     */
    public function findByUser(User $user)
    {
        return $this->findBy(['user' => $user->getId()]);
    }

    /**
     * Returns an array of all the orders, starting at $offsetStart, limited to $perPage
     *
     * @param array   $baseIds
     * @param integer $offsetStart
     * @param integer $perPage
     * @param string  $sort
     *
     * @return Order[]
     */
    public function listOrders($baseIds, $offsetStart = 0, $perPage = 20, $sort = "created_on")
    {
        $qb = $this
            ->createQueryBuilder('o');

         if (!empty($baseIds)) {
             $qb
                 ->innerJoin('o.elements', 'e')
                 ->where($qb->expr()->in('e.baseId', $baseIds))
                 ->groupBy('o.id');
         }

         if ($sort === 'user') {
             $qb->orderBy('o.user', 'ASC');
         } elseif ($sort === 'usage') {
             $qb->orderBy('o.orderUsage', 'ASC');
         } else {
             $qb->orderBy('o.createdOn', 'DESC');
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
     * @return int
     */
    public function countTotalOrders(array $baseIds = [])
    {
        $builder = $this->createQueryBuilder('o');
        $builder->select($builder->expr()->countDistinct('o.id'));

        if (!empty($baseIds)) {
            $builder
                ->innerJoin('o.elements', 'e')
                ->where($builder->expr()->in('e.baseId', $baseIds));
        }

        return $builder->getQuery()->getSingleScalarResult();
    }
}
