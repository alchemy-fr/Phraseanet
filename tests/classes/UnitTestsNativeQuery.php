<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\User;

class UnitTestsNativeQuery
{
    private $conn;
    private $em;

    public function __construct(Connection $conn, EntityManager $em)
    {
        $this->conn = $conn;
        $this->em = $em;
    }

    public function getModelForUser(User $user, array $basList)
    {
        $rs = $this->conn->fetchAll("
                SELECT u.id
                FROM Users u
                    INNER JOIN basusr b ON (b.usr_id=u.id)
                WHERE u.model_of = :user_id
                  AND b.base_id IN (" . implode(', ', $basList) . ")
                  AND u.deleted='0'
                GROUP BY u.id", [':usr_id' => $user->getId()]);

        return array_map(function ($row) {
            return $this->em->getRepository('Phraseanet:User', $row['id']);
        }, $rs);
    }

    public function getAdminsOfBases(array $basList)
    {
        $rs = $this->conn->fetchAll('
            SELECT b.base_id, u.id FROM Users u, basusr b
            WHERE u.id = b.usr_id
                AND b.base_id IN (' . implode(', ', $basList) . ')
                AND u.model_of IS NULL
                AND b.actif="1"
                AND b.canadmin="1"
                AND u.deleted="0"');

        return array_map(function ($row) {
            return [$this->em->getRepository('Phraseanet:User', $row['id']), 'base_id' => $row['base_id']];
        }, $rs);
    }

    public function getFeedItemByCollection(\collection $coll, $offsetStart = 0, $quantity = 100)
    {
        $rs = $this->conn->fetchAll('
            SELECT i.id FROM FeedItems i, record r, coll c
            WHERE c.coll_id = :coll_id
                AND r.coll_id = c.coll_id
                AND i.record_id = r.record_id
                LIMIT '.(int) $offsetStart.', '.(int) $quantity, [':coll_id' => $coll->get_coll_id()]);

        return array_map(function ($row) {
            return $this->em->getRepository('Phraseanet:FeedItem', $row['id']);
        }, $rs);
    }

    public function getBasketElementsByCollection(\collection $coll, $offsetStart = 0, $quantity = 100)
    {
        $rs = $this->conn->fetchAll('
            SELECT e.id FROM BasketElements e, record r, coll c
            WHERE c.coll_id = :coll_id
                AND r.coll_id = c.coll_id
                AND e.record_id = r.record_id
                LIMIT '.(int) $offsetStart.', '.(int) $quantity, [':coll_id' => $coll->get_coll_id()]);

        return array_map(function ($row) {
            return $this->em->getRepository('Phraseanet:BasketElement', $row['id']);
        }, $rs);
    }

    public function getStoryWZByCollection(\collection $coll, $offsetStart = 0, $quantity = 100)
    {
        $rs = $this->conn->fetchAll('
            SELECT s.id FROM StoryWZ s, record r, coll c
            WHERE c.coll_id = :coll_id
                AND r.coll_id = c.coll_id
                AND s.record_id = r.record_id
                LIMIT '.(int) $offsetStart.', '.(int) $quantity, [':coll_id' => $coll->get_coll_id()]);

        return array_map(function ($row) {
            return $this->em->getRepository('Phraseanet:StoryWZ', $row['id']);
        }, $rs);
    }
}
