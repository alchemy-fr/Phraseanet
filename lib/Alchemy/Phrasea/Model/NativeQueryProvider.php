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

    public function getUsersRegistrationDemand(array $basList)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\User', 'u');
        $rsm->addScalarResult('date_demand', 'date_demand');
        $rsm->addScalarResult('base_demand', 'base_demand');

        $selectClause = $rsm->generateSelectClause();

        return $this->em->createNativeQuery(
            "SELECT d.date_modif AS date_demand, d.base_id AS base_demand, " . $selectClause . "\n"
            . " FROM (demand d INNER JOIN Users u ON d.usr_id=u.id AND d.en_cours=1 AND u.deleted=0)\n"
            . " WHERE (base_id='" . implode("' OR base_id='", $basList) . "')\n"
            . " ORDER BY d.usr_id DESC, d.base_id ASC",
            $rsm
        )->getResult();
    }

    public function getModelForUser(User $user, array $basList)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\User', 'u');

        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery(
            "SELECT " . $selectClause . " FROM Users u INNER JOIN basusr b ON (b.usr_id=u.id)\n"
            . " WHERE u.model_of = :user_id\n"
            . "  AND b.base_id IN (" . implode(', ', $basList) . ")\n"
            . "  AND u.deleted='0'\n"
            . " GROUP BY u.id",
            $rsm
        );

        $query->setParameter(':user_id', $user->getId());

        return $query->getResult();
    }

    public function getAdminsOfBases(array $basList)
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('Alchemy\Phrasea\Model\Entities\User', 'u');
        $rsm->addScalarResult('base_id', 'base_id');
        $selectClause = $rsm->generateSelectClause();

        $query = $this->em->createNativeQuery(
            "SELECT b.base_id, ".$selectClause." FROM Users u, basusr b\n"
            . " WHERE u.id = b.usr_id\n"
            . "  AND b.base_id IN (" . implode(', ', $basList) . ")\n"
            . "  AND u.model_of IS NULL\n"
            . "  AND b.actif=1\n"
            . "  AND b.canadmin=1\n"
            . "  AND u.deleted=0",
            $rsm
        );

        return $query->getResult();
    }
}
