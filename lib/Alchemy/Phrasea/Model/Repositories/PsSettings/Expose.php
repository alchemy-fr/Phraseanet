<?php

namespace Alchemy\Phrasea\Model\Repositories\PsSettings;

use Alchemy\Phrasea\Model\Repositories\PsSettingKeysRepository;
use Alchemy\Phrasea\Model\Repositories\PsSettings\Expose\Instance;
use Alchemy\Phrasea\Model\Repositories\PsSettingsRepository;
use Doctrine\ORM\NonUniqueResultException;

class Expose
{
    private $psSettingsRepository;
    private $psSettingKeysRepository;

    public function __construct(PsSettingsRepository $psSettingsRepository, PsSettingKeysRepository $psSettingKeysRepository)
    {
        $this->psSettingsRepository    = $psSettingsRepository;
        $this->psSettingKeysRepository = $psSettingKeysRepository;
    }

    /**
     * @return Instance[]
     */
    public function getInstances($userId = null): array
    {
        $ret = [];

        foreach($this->psSettingsRepository->get('EXPOSE') as $ex) {
            $ix = new Instance(
                $this->psSettingsRepository,
                $this->psSettingKeysRepository,
                $ex
            );
            if(is_null($userId) || $ix->canSee($userId)) {
                $ret[] = $ix;
            }
        }

        return $ret;
    }

    /**
     * create a new "Expose" without settings yet
     *
     * @param string $name
     * @return Instance
     * @throws NonUniqueResultException   if the name already exists
     */
    public function create(string $name)
    {
        $e = $this->psSettingsRepository->createUnique('EXPOSE', 'name', null, ['valueVarchar' => $name]);
        return new Instance(
            $this->psSettingsRepository,
            $this->psSettingKeysRepository,
            $e
        );
    }
}