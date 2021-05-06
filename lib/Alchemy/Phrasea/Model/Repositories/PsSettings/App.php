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
        $this->psSettingsRepository->setValues($e, $values);
        $this->psSettingsRepository->getEntityManager()->persist($e);
        $this->psSettingsRepository->getEntityManager()->flush();

        return $e;
    }

    /**
     * @param $userId
     * @param $aceName
     * @return PsSettings|null
     */
    protected function getACE($userId, $aceName)
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

    private function setACE(int $userId, string $aceName, int $value)
    {
        $ace = $this->psSettingsRepository->getOrCreateUnique(
            'ACE',
            $aceName,
            $this->instanceEntity
        );
        $userKey = $this->psSettingKeysRepository->getOrCreateUnique(
            'user_id',
            $ace,
            ['valueVarchar' => (string)$userId]
        );

        return $ace;
    }

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
}