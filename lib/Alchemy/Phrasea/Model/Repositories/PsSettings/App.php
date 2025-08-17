<?php

namespace Alchemy\Phrasea\Model\Repositories\PsSettings;

use Alchemy\Phrasea\Model\Entities\PsSettings;
use Alchemy\Phrasea\Model\Repositories\PsSettingKeysRepository;
use Alchemy\Phrasea\Model\Repositories\PsSettingsRepository;

class App
{
    /** @var PsSettingsRepository  */
    protected $psSettingsRepository;
    /** @var PsSettingKeysRepository  */
    protected $psSettingKeysRepository;
    /** @var PsSettings  */
    protected $instanceEntity;

    public function __construct(PsSettingsRepository $psSettingsRepository, PsSettingKeysRepository $psSettingKeysRepository, PsSettings $instanceEntity)
    {
        $this->psSettingsRepository    = $psSettingsRepository;
        $this->psSettingKeysRepository = $psSettingKeysRepository;
        $this->instanceEntity          = $instanceEntity;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->instanceEntity->getId();
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->instanceEntity->getValueString();
    }

    protected function getSettings()
    {
        return $this->psSettingsRepository->get('SETTING', null, $this->instanceEntity);
    }

    protected function getOrSetSetting(string $name, array $values = null)
    {
        if(is_null($values)) {
            // get
            return $this->psSettingsRepository->getUnique('SETTING', $name, $this->instanceEntity);
        }
        // set
        $child = $this->psSettingsRepository->getOrCreateUnique('SETTING', $name, $this->instanceEntity);
        $child->setValues($values);

        $this->psSettingsRepository->getEntityManager()->persist($this->instanceEntity);    // cascade child
        $this->psSettingsRepository->getEntityManager()->flush();

        return $child;
    }

    /**
     * a ACE is a bool
     * to read a ACE, call with no value, e.g. "$cansee = getOrSetACE(666, 'cansee')";
     * to set a ACE, call by passing value as bool, e.g. "getOrSetACE(666, 'cansee', true)";
     * inspired by jquery...
     *
     * nb : because reading a non-existing ACE returns always false
     * setting a ACE to false simply deletes it !
     * Anyway the bool value (1='yes') is used, and one can still set the value to 0 (false) in sql to enforce a 'no'.
     *
     * @param int $userId
     * @param string $aceName
     * @param bool|null $value
     * @return bool
     */
    protected function getOrSetACE(int $userId, string $aceName, bool $value = null)
    {
        $ace = null;
        if(is_null($value)) {
            // get ace
            $ace = $this->getACE($userId, $aceName);
        }
        else {
            // set ace
            if($value) {
                // we set ace to true
                $ace = $this->setACE($userId, $aceName, 1);
            }
            else {
                // we set ace to false : better delete it
                $this->deleteACE($userId, $aceName);
            }
        }

        return $ace && $ace->getValueInt() == 1;
    }

    /**
     * @param $userId
     * @param $aceName
     * @return PsSettings|null
     */
    private function getACE($userId, $aceName)
    {
        $r = $this->psSettingsRepository->get(
            'ACE',
            $aceName,
            $this->instanceEntity,
            [],
            ['user_id' => ['valueString'=>$userId]]
        );

        return empty($r) ? null : $r[0];
    }

    private function setACE(int $userId, string $aceName, bool $value)
    {
        // a ACE entry is a bool (stored into valueInt as 0 or 1)
        $ace = $this->psSettingsRepository->getOrCreateUnique(
            'ACE',
            $aceName,
            $this->instanceEntity
        )->setValueInt($value ? 1 : 0);

        // a ACE has a key that refers to a a user, stored as an id into valueVarchar (could be int... but who says an id is always an int ?)
        $ke = $ace->setKey('user_id', ['valueString' => (string)$userId]);

        $this->psSettingKeysRepository->getEntityManager()->persist($ke);
        $this->psSettingKeysRepository->getEntityManager()->flush();

        return $ace;
    }

    private function deleteACE(int $userId, string $aceName)
    {
        $this->psSettingsRepository->delete('ACE', $aceName, $this->instanceEntity, [], ['user_id'=>['valueString'=>$userId]]);
    }

    public function asArray(): array
    {
        return $this->instanceEntity->asArray();
    }
}