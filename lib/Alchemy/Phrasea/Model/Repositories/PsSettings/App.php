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
        return $this->instanceEntity->getValueVarchar();
    }

    protected function getSettings()
    {
        return $this->psSettingsRepository->get('SETTING', null, $this->instanceEntity);
    }

    protected function setSetting(string $name, array $values)
    {
        $e = $this->psSettingsRepository->getOrCreateUnique('SETTING', $name, $this->instanceEntity);
        $e->setValues($values);
        $this->psSettingsRepository->getEntityManager()->persist($e);
        $this->psSettingsRepository->getEntityManager()->flush();

        return $e;
    }

    /**
     * a ACE is a bool
     * to read a ACE, call with no value, e.g. "$cansee = readOrSetACE(666, 'cansee')";
     * to set a ACE, call by passing value as bool, e.g. "readOrSetACE(666, 'cansee', true)";
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
    protected function readOrSetACE(int $userId, string $aceName, $value = null)
    {
        $ace = null;
        if(!is_null($value)) {
            // write ace
            if($value) {
                // we set ace to true
                $ace = $this->setACE($userId, $aceName, 1);
            }
            else {
                // we set ace to false : better delete it
                $this->deleteAce($userId, $aceName);
            }
        }
        else {
            // read ace
            $ace = $this->getACE($userId, $aceName);
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
            ['user_id' => ['valueVarchar'=>$userId]]
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
        $ke = $ace->addKey('user_id', ['valueVarchar' => (string)$userId]);

        $this->psSettingKeysRepository->getEntityManager()->persist($ke);
        $this->psSettingKeysRepository->getEntityManager()->flush();

        return $ace;
    }

    private function deleteACE(int $userId, string $aceName)
    {
        $this->psSettingsRepository->delete('ACE', $aceName, $this->instanceEntity, [], ['user_id'=>['valueVarchar'=>$userId]]);
    }

    public function asArray()
    {
        return $this->instanceEntity->asArray();
    }
}