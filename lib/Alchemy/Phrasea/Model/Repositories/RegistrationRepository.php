<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Registration;
use Doctrine\ORM\EntityRepository;
use Alchemy\Phrasea\Model\Entities\User;

/**
 * RegistrationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RegistrationRepository extends EntityRepository
{
    /**
     * Displays registrations for user on provided collection.
     *
     * @param User          $user
     * @param \collection[] $collections
     *
     * @return Registration[]
     */
    public function getUserRegistrations(User $user, array $collections)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where($qb->expr()->eq('d.user', ':user'));
        $qb->setParameter(':user', $user->getId());

        if (count($collections) > 0) {
            $qb->andWhere('d.baseId IN (:bases)');
            $qb->setParameter(':bases', array_map(function (\collection $collection) {
                return $collection->get_base_id();
            }, $collections));
        }

        $qb->orderBy('d.created', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get Current pending registrations.
     *
     * @param \collection[] $collections
     * @return Registration[]
     */
    public function getPendingRegistrations(array $collections)
    {
        $builder = $this->createQueryBuilder('r');
        $builder->where('r.pending = 1');

        if (!empty($collections)) {
            $builder->andWhere('r.baseId IN (:bases)');
            $builder->setParameter('bases', array_map(function (\collection $collection) {
                return $collection->get_base_id();
            }, $collections));
        }

        $builder->orderBy('r.created', 'DESC');

        return $builder->getQuery()->getResult();
    }

    /**
     * Gets registration registrations for a user.
     *
     * @param User $user
     *
     * @return array
     */
    public function getRegistrationsSummaryForUser(User $user)
    {
        $data = [];
        $rsm = $this->createResultSetMappingBuilder('d');
        $rsm->addScalarResult('sbas_id','sbas_id');
        $rsm->addScalarResult('bas_id','bas_id');
        $rsm->addScalarResult('dbname','dbname');
        $rsm->addScalarResult('time_limited', 'time_limited');
        $rsm->addScalarResult('limited_from', 'limited_from');
        $rsm->addScalarResult('limited_to', 'limited_to');
        $rsm->addScalarResult('actif', 'actif');

        // nb: UNIX_TIMESTAMP will return null if date is 0000-00-00 00:00:00
        $sql = "SELECT dbname, sbas.sbas_id, time_limited,\n"
            . "  UNIX_TIMESTAMP( limited_from ) AS limited_from,\n"
            . "  UNIX_TIMESTAMP( limited_to ) AS limited_to,\n"
            . "  bas.server_coll_id, Users.id, basusr.actif,\n"
            . "  bas.base_id AS bas_id, " . $rsm->generateSelectClause(['d' => 'd',]) . "\n"
            . "FROM (Users, bas, sbas)\n"
            . "  LEFT JOIN basusr ON ( Users.id = basusr.usr_id AND bas.base_id = basusr.base_id )\n"
            . "  LEFT JOIN Registrations d ON ( d.user_id = Users.id AND bas.base_id = d.base_id )\n"
            . "WHERE bas.active = 1 AND bas.sbas_id = sbas.sbas_id\n"
            . "  AND Users.id = ?\n"
            . "  AND ISNULL(model_of)";

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter(1, $user->getId());

        foreach ($query->getResult() as $row) {
            $registrationEntity = $row[0];
            $in_time = null;
            if(($row['time_limited'] !== null) && ($row['limited_from'] !== null || $row['limited_to'] !== null)) {
                $in_time = true;
                if($row['limited_from'] !== null && time() < $row['limited_from']) {
                    $in_time = false;
                }
                elseif($row['limited_to'] !== null && time() > $row['limited_to']) {
                    $in_time = false;
                }
            }
            $data[$row['sbas_id']][$row['bas_id']] = [
                'base-id' => $row['bas_id'],
                'db-name' => $row['dbname'],
                'active' => self::nullOrBoolean($row['actif']),
                'time-limited' => self::nullOrBoolean($row['time_limited']),
                'in-time' => $in_time,
                'registration' => $registrationEntity
            ];
        }

        return $data;
    }

    public static function nullOrBoolean($v)
    {
        if(!is_null($v)) {
            $v = (boolean)$v;
        }
        return $v;
    }
}
