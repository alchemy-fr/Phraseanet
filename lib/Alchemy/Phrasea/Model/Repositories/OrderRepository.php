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
    public function listOrders($baseIds, $offsetStart = 0, $perPage = 20, $sort = "created_on", $filtre = null)
    {
        $qb = $this
            ->createQueryBuilder('o');

         if (!empty($baseIds)) {
             if (!empty($filtre))
             {
                 $qb
                     ->innerJoin('o.elements', 'e')
                     ->where($qb->expr()->in('e.baseId', $baseIds));

                 if (NULL !== $filtre['todo'] && '' !== $filtre['todo'])
                 {
                     $qb
                         ->andWhere('o.todo = '.$filtre['todo']);
                 }


                 if (isset($filtre['created_on']) && '' !== $filtre['created_on'])
                 {
                     $createdOn = '';
                     switch ((int)$filtre['created_on'])
                     {
                         case 0:    //this week
                             $time = strtotime(date("Y-m-d 00:00:00"));
                             $weekStartDate = date('Y-m-d',strtotime("last Monday", $time));
                             $createdOn = $weekStartDate;
                             break;

                         case 1:    //last week
                             $time = strtotime('last week');
                             $lastWeekStartDate = date('Y-m-d',strtotime("Monday", $time));
                             $createdOn = $lastWeekStartDate;
                             break;

                         case 2:    //last month
                             $lastMonthStartDate = date("Y-m-d", strtotime("first day of previous month"));
                             $createdOn = $lastMonthStartDate;
                             break;

                         default:
                             break;
                     }

                     if ('' !== $createdOn)
                     {

                         $qb
                             ->andWhere("o.createdOn >= '" . $createdOn . "'");


                     }
                 }

                /* if (NULL !== $filtre['deadline'] && '' !== $filtre['deadline'])
                 {
                     $deadline = '';

                     switch ((int)$filtre['deadline'])
                     {
                         case 0:    //this week
                             $time = strtotime(date("Y-m-d 00:00:00"));
                             $weekStartDate = date('Y-m-d',strtotime("last Sunday", $time));
                             $deadline = $weekStartDate;
                             break;

                         case 1:    //last week
                             $time = strtotime('last week');
                             $lastWeekStartDate = date('Y-m-d',strtotime("Sunday", $time));
                             $deadline = $lastWeekStartDate;
                             break;

                         case 2:    //last month
                             $lastMonthStartDate = date("Y-m-d", strtotime("first day of previous month"));
                             $deadline = $lastMonthStartDate;
                             break;

                         default:
                             break;
                     }

                     if ('' !== $deadline)
                     {
                         $qb
                             ->andWhere("o.deadline <= '" . $deadline . "'");
                     }
                 } */


                 if(isset($filtre['limit'])) {
                        $createdOn = date('Y-m-d', strtotime($filtre['limit']['date']));
                        if($filtre['limit']['type'] == '3') { //before
                            $qb->andWhere("o.createdOn < '" . $createdOn . "'");
                        }else { //after
                            $qb->andWhere("o.createdOn > '" . $createdOn . "'");
                        }
                  }

                 $qb->groupBy('o.id');
             }
             else
             {
                 $qb
                     ->innerJoin('o.elements', 'e')
                     ->where($qb->expr()->in('e.baseId', $baseIds))
                     ->groupBy('o.id');
             }
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
