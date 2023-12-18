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
     * @param array $baseIds
     * @param integer $offsetStart
     * @param integer $perPage
     * @param string $sort
     * @param ArrayCollection $filters
     * @return Order[]
     */
    public function listOrders($baseIds, $offsetStart = 0, $perPage = 20, $sort = "created_on", $filters = [])
    {
        $qb = $this
            ->createQueryBuilder('o');

        $this->performQuery($qb, $baseIds, $filters);
        $qb->groupBy('o.id');

        if ($sort === 'user') {
            $qb->orderBy('o.user', 'ASC');
        }
        elseif ($sort === 'usage') {
            $qb->orderBy('o.orderUsage', 'ASC');
        }
        else {
            $qb->orderBy('o.createdOn', 'DESC');
        }

        $qb
            ->setFirstResult((int)$offsetStart)
            ->setMaxResults(max(10, (int)$perPage));

        return $qb->getQuery()->getResult();
    }

    public function performQuery($qb, $baseIds, $filters)
    {
        if (!empty($baseIds)) {
            $qb
                ->innerJoin('o.elements', 'e')
                ->where($qb->expr()->in('e.baseId', $baseIds));
            if ($filters['todo'] == Order::STATUS_TODO) {
                $qb
                    ->andWhere('o.todo != 0');
            }
            elseif ($filters['todo'] == Order::STATUS_PROCESSED) {
                $qb
                    ->andWhere('o.todo = 0');
            }

            $createdOn = '';
            switch ($filters['created_on']) {
                case Order::STATUS_CURRENT_WEEK:    //this week
                    $time = strtotime(date("Y-m-d 00:00:00"));
                    //check if today is monday
                    if (date('D', $time) == 'Mon') {
                        $weekStartDate = date('Y-m-d', strtotime("Monday", $time));
                    }
                    else {
                        $weekStartDate = date('Y-m-d', strtotime("last Monday", $time));
                    }
                    $createdOn = $weekStartDate;
                    $qb->andWhere("o.createdOn >= '" . $createdOn . "'");
                    break;

                case Order::STATUS_PAST_WEEK:    //last week
                    $time = strtotime('last week');
                    $lastWeekStartDate = date('Y-m-d', strtotime("Monday", $time));
                    $createdOn = $lastWeekStartDate;
                    $qb->andWhere("o.createdOn >= '" . $createdOn . "'");
                    break;

                case Order::STATUS_PAST_MONTH:    //last month
                    $lastMonthStartDate = date("Y-m-d", strtotime("first day of previous month"));
                    $createdOn = $lastMonthStartDate;
                    $qb->andWhere("o.createdOn >= '" . $createdOn . "'");
                    break;

                case Order::STATUS_BEFORE:    //before specific date
                    if (isset($filters['limit']['date'])) {
                        $createdOn = date('Y-m-d', strtotime($filters['limit']['date']));
                        $qb->andWhere("o.createdOn < '" . $createdOn . "'");
                    }
                    break;

                case Order::STATUS_AFTER:    //before specific date
                    if (isset($filters['limit']['date'])) {
                        $createdOn = date('Y-m-d', strtotime($filters['limit']['date']));
                        $qb->andWhere("o.createdOn > '" . $createdOn . "'");
                    }
                    break;

                case Order::STATUS_NO_FILTER:
                    //no filtering by date
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Returns the total number of orders from an array of base_id and filters
     *
     * @param array $baseIds
     * @param array $filters
     * @return int
     */
    public function countTotalOrders(array $baseIds = [], $filters = [])
    {
        $builder = $this->createQueryBuilder('o');
        $builder->select($builder->expr()->countDistinct('o.id'));
        $this->performQuery($builder, $baseIds, $filters);

        return $builder->getQuery()->getSingleScalarResult();
    }

    public function findAllTodo()
    {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.todo != 0');

        return $qb->getQuery()->getResult();
    }
}