<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

abstract class patchAbstract implements patchInterface
{
    protected function loadUser(EntityManager $em, $usrId)
    {
        try {
            return $em->createQuery('SELECT PARTIAL u.{id} FROM Phraseanet:User u WHERE u.id = :id')
                ->setParameters(['id' => $usrId])
                ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                ->getSingleResult();
        } catch (NoResultException $e) {

        }
    }

    protected function tableExists(EntityManager $em, $tableName)
    {
        return (Boolean) $em->createNativeQuery(
            'SHOW TABLE STATUS WHERE Name="'.$tableName.'" COLLATE utf8_bin ', (new ResultSetMapping())->addScalarResult('Name', 'Name')
        )->getOneOrNullResult();
    }

    protected function tableHasField(EntityManager $em, $tableName, $fieldName)
    {
        if (!$this->tableExists($em, $tableName)) {
            return false;
        }

        return $em
            ->getConnection()
            ->getSchemaManager()
            ->listTableDetails($tableName)
            ->hasColumn($fieldName);
    }

    public function getDoctrineMigrations()
    {
        return [];
    }
}
