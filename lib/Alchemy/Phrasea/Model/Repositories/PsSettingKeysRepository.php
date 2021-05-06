<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\PsSettingKeys;
use Alchemy\Phrasea\Model\Entities\PsSettings;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * PsSettingKeysRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PsSettingKeysRepository extends EntityRepository
{
    public function get(string $key=null, PsSettings $setting=null, array $values = [], int $maxResult=null)
    {
        $crit = [];
        if(!is_null($key)) {
            $crit['keyName'] = $key;
        }
        if(!is_null($setting)) {
            $crit['setting'] = $setting;
        }
        foreach($values as $k => $v) {
            $crit[$k] = $v;
        }

        return $this->findBy($crit, null, $maxResult);
    }

    public function getOrCreateUnique(string $key=null, PsSettings $setting=null, array $values = [])
    {
        $this->_em->beginTransaction();

        $e = $this->get($key, $setting, $values);
        if(count($e) === 0) {
            $e = [$this->insert($key, $setting, $values)];
        }
        $this->_em->getConnection()->commit();

        if(count($e) !== 1) {
            throw new NonUniqueResultException();
        }
        return $e[0];
    }

    private function insert(string $key=null, PsSettings $setting=null, array $values = [])
    {
        $e = new PsSettingKeys();

        if(!is_null($setting)) {
            $e->setSetting($setting);
        }
        if(!is_null($key)) {
            $e->setKeyName($key);
        }
        $this->setValues($e, $values);

        $this->_em->persist($e);
        $this->_em->flush();

        return $e;
    }

    public function setValues(PsSettingKeys $e, array $values)
    {
        foreach ($values as $k => $v) {
            switch ($k) {
                case 'valueText':
                    $e->setValueText($v);
                    break;
                case 'valueInt':
                    $e->setValueInt($v);
                    break;
                case 'valueVarchar':
                    $e->setValueVarchar($v);
                    break;
            }
        }
    }

}
