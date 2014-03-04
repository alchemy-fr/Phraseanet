<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Alchemy\Phrasea\Model\Entities\User;

class NativeQueryProvider
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getModelForUser(User $user, array $basList)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\User', 'u');

        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery("
                SELECT " . $selectClause . "
                FROM Users u
                    INNER JOIN basusr b ON (b.usr_id=u.id)
                WHERE u.model_of = :user_id
                  AND b.base_id IN (" . implode(', ', $basList) . ")
                  AND u.deleted='0'
                GROUP BY u.id", $rsm);

        $query->setParameter(':user_id', $user->getId());

        return $query->getResult();
    }

    public function getAdminsOfBases(array $basList)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\User', 'u');
        $rsm->addScalarResult('base_id', 'base_id');
        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery('
            SELECT b.base_id, '.$selectClause.' FROM Users u, basusr b
            WHERE u.id = b.usr_id
                AND b.base_id IN (' . implode(', ', $basList) . ')
                AND u.model_of IS NULL
                AND b.actif="1"
                AND b.canadmin="1"
                AND u.deleted="0"', $rsm
        );

        return $query->getResult();
    }

    public function getFeedItemByCollection(\collection $coll, $offsetStart = 0, $quantity = 100)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\FeedItem', 'i');
        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery('
            SELECT '.$selectClause.' FROM FeedItems i, record r, coll c
            WHERE c.coll_id = :coll_id
                AND r.coll_id = c.coll_id
                AND i.record_id = r.record_id
                LIMIT '.(int) $offsetStart.', '.(int) $quantity, $rsm
        );
        $query->setParameter(':coll_id', $coll->get_coll_id());

        return $query->getResult();
    }

    public function getBasketElementsByCollection(\collection $coll, $offsetStart = 0, $quantity = 100)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\BasketElement', 'e');
        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery('
            SELECT '.$selectClause.' FROM BasketElements e, record r, coll c
            WHERE c.coll_id = :coll_id
                AND r.coll_id = c.coll_id
                AND e.record_id = r.record_id
                LIMIT '.(int) $offsetStart.', '.(int) $quantity, $rsm
        );
        $query->setParameter(':coll_id', $coll->get_coll_id());

        return $query->getResult();
    }

    public function getStoryWZByCollection(\collection $coll, $offsetStart = 0, $quantity = 100)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\StoryWZ', 's');
        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery('
            SELECT '.$selectClause.' FROM StoryWZ s, record r, coll c
            WHERE c.coll_id = :coll_id
                AND r.coll_id = c.coll_id
                AND s.record_id = r.record_id
                LIMIT '.(int) $offsetStart.', '.(int) $quantity, $rsm
        );
        $query->setParameter(':coll_id', $coll->get_coll_id());

        return $query->getResult();
    }
}
