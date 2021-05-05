<?php

namespace Alchemy\Phrasea\Model\Repositories\PsSettings;

use Alchemy\Phrasea\Model\Entities\PsSettings;
use Alchemy\Phrasea\Model\Repositories\PsSettingsRepository;

class App
{
    /** @var PsSettingsRepository  */
    protected $psSettingsRepository;
    /** @var PsSettings  */
    protected $instanceEntity;

    public function __construct(PsSettingsRepository $psSettingsRepository, PsSettings $instanceEntity)
    {
        $this->psSettingsRepository = $psSettingsRepository;
        $this->instanceEntity = $instanceEntity;
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

}