<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\PsSettingKeys;
use Alchemy\Phrasea\Model\Entities\PsSettings;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\Expr\Join;

/**
 * PsSettingsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PsSettingsRepository extends EntityRepository
{
    public function exists(string $role=null, string $name=null, PsSettings $parent=null, array $values = []): bool
    {
        return !empty($this->get($role, $name, $parent, $values, [], 1));
    }

    /**
     * get rows that match every non-null arguments
     * consequence : this can't be used to search a null...
     *
     * eg.
     *   get(
     *      'ACE',
     *      'cansee',
     *      null,
     *      ['valueInt' => 1],
     *      ['user_id' => ['valueVarchar' => '1']]
     *   );
     *
     * @param string|null $role
     * @param string|null $name
     * @param PsSettings|null $parent
     * @param array $values k=>v, where k = "valueText" | "valueInt" | "valueVarchar"
     * @param array $keys   keyname=>[[k1=>v1], [k2=>v2], ...]
     * @param int|null $limit
     * @return PsSettings[]
     */
    public function get(string $role=null, string $name=null, PsSettings $parent=null, array $values = [], array $keys = [], int $limit = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')
            ->from('Phraseanet:PsSettings', 's');
        if(!is_null($role)) {
            $qb->andWhere('s.role = :role')->setParameter('role', $role);
        }
        if(!is_null($name)) {
            $qb->andWhere('s.name = :name')->setParameter('name', $name);
        }
        if(!is_null($parent)) {
            $qb->andWhere('s.parent = :parent')->setParameter('parent', $parent);
        }
        foreach($values as $k => $v) {
            $qb->andWhere('s.'.$k.' = :'.$k)->setParameter($k, $v);
        }
        $nParm = 1;
        $nAlias = 1;
        foreach($keys as $k => $values) {
            // each key to match have it's own join/alias
            $alias = 'sk'.$nAlias;
            $parm  = 'ky'.$nAlias++;
            $qb->innerJoin('Phraseanet:PsSettingKeys', $alias, Join::WITH, '('.$alias.'.parent'.' = s AND '.$alias.'.name = :'.$parm.')')
                ->setParameter($parm, $k);
            // each key value to match (valueVarchar, valueInt or valueText) have it's own where
            foreach($values as $k => $v) {
                $parm = 'v'.$nParm++;
                $qb->andWhere($alias.'.'.$k.' = :'.$parm)->setParameter($parm, $v);
            }
        }

        if(!is_null($limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    // todo : use cascade !
    public function delete(string $role=null, string $name=null, PsSettings $parent=null, array $values = [], array $keys = [])
    {
        $this->_em->beginTransaction();

        foreach ($this->get($role, $name, $parent, $values, $keys) as $se) {
            // delete all keys of this setting
            $se->getKeys()->clear();
            // delete the setting
            $this->_em->remove($se);
        }
        $this->getEntityManager()->flush();
        $this->getEntityManager()->commit();
    }

    /**
     * find a unique row, creating it if it did not exist
     *
     * @param string|null $role
     * @param string|null $name
     * @param PsSettings|null $parent
     * @param array $values
     * @return PsSettings
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ConnectionException
     */
    public function getOrCreateUnique(string $role=null, string $name=null, PsSettings $parent=null, array $values = [])
    {
        $this->getEntityManager()->beginTransaction();

        $e = $this->get($role, $name, $parent, $values);
        if(count($e) === 0) {
            $e = $this->insert($role, $name, $parent, $values);
            if(!is_null($parent)) {
                $parent->getChildren()->add($e);
            }
            $e = [$e];
        }
        $this->getEntityManager()->commit();

        if(count($e) !== 1) {
            throw new NonUniqueResultException();
        }
        return $e[0];
    }

    public function getUnique(string $role=null, string $name=null, PsSettings $parent=null, array $values = [])
    {
        $e = $this->get($role, $name, $parent, $values);
        if(count($e) > 1) {
            throw new NonUniqueResultException();
        }
        return count($e) === 1 ? $e[0] : null;
    }

    /**
     * create a new row which must not exist before
     *
     * @param string|null $role
     * @param string|null $name
     * @param PsSettings|null $parent
     * @param array $values
     * @return PsSettings
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ConnectionException
     */
    public function createUnique(string $role=null, string $name=null, PsSettings $parent=null, array $values = [])
    {
        $this->getEntityManager()->beginTransaction();

        if(!$this->exists($role, $name, $parent, $values)) {
            $e = $this->insert($role, $name, $parent, $values);

            $this->getEntityManager()->commit();

            return $e;
        }
        else {
            // already exist
            throw new NonUniqueResultException();
        }
    }

    /**
     * insert a row, NO TRANSACTION
     * @param string|null $role
     * @param string|null $name
     * @param PsSettings|null $parent
     * @param array $values
     * @return PsSettings
     * @throws OptimisticLockException
     */
    private function insert(string $role=null, string $name=null, PsSettings $parent=null, array $values = [])
    {
        $e = new PsSettings();

        if(!is_null($parent)) {
            $e->setParent($parent);
        }
        if(!is_null($role)) {
            $e->setRole($role);
        }
        if(!is_null($name)) {
            $e->setName($name);
        }
        $e->setValues($values);

        $this->_em->persist($e);
        $this->_em->flush();

        return $e;
    }

    public function fillFromArray(PsSettings $e, array $a, $depth = 0)
    {
        if(array_key_exists('role', $a) && is_scalar(($v = $a['role']))) {
            $e->setRole($v);
        }
        if(array_key_exists('name', $a) && is_scalar(($v = $a['name']))) {
            $e->setName($v);
        }
        if(array_key_exists('valueText', $a) && is_scalar(($v = $a['valueText']))) {
            $e->setValueText($v);
        }
        if(array_key_exists('valueString', $a) && is_scalar(($v = $a['valueString']))) {
            $e->setValueString($v);
        }
        if(array_key_exists('valueInt', $a) && is_scalar(($v = $a['valueInt']))) {
            $e->setValueInt($v);
        }

        if(array_key_exists('keys', $a)) {
            foreach ($a['keys'] as $k) {
                $key = PsSettingKeys::fromArray($k);
                $key->setSetting($e);
                $e->getKeys()->add($key);
            }
        }

        if(array_key_exists('children', $a)) {
            foreach ($a['children'] as $c) {
                $child = new PsSettings();
                $this->fillFromArray($child, $c, $depth+1);
                $child->setParent($e);
                $e->getChildren()->add($child);
            }
        }

        if($depth === 0) {
            // flush only in the end
            $this->getEntityManager()->persist($e);
            $this->getEntityManager()->flush();
        }
    }

    public function getEntityManager()
    {
        return $this->_em;
    }

}
