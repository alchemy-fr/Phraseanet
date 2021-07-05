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
     *  Get a psSetting by name
     *
     * @param string $exposeName
     * @return Instance|null
     */
    public function getInstance(string $exposeName)
    {
        $exs = $this->psSettingsRepository->get('EXPOSE', 'name', null, ['valueString' => $exposeName]);

        if (!empty($exs)) {
            $ex = current($exs);

            return new Instance(
                $this->psSettingsRepository,
                $this->psSettingKeysRepository,
                $ex
            );
        }

        return null;
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
        $e = $this->psSettingsRepository->createUnique('EXPOSE', 'name', null, ['valueString' => $name]);
        return new Instance(
            $this->psSettingsRepository,
            $this->psSettingKeysRepository,
            $e
        );
    }

    public function fromArray(array $a)
    {
        if(!array_key_exists('role', $a) || $a['role'] !== 'EXPOSE') {
            throw new \InvalidArgumentException("Expose setting requires role=\"EXPOSE\"");
        }
        if(!array_key_exists('name', $a) || $a['name'] !== 'name') {
            throw new \InvalidArgumentException("Expose setting requires name=\"name\"");
        }
        if(!array_key_exists('valueString', $a) || (string)$a['valueString'] == '') {
            throw new \InvalidArgumentException("Expose setting requires a name in 'value_string' ");
        }

        $e = $this->psSettingsRepository->createUnique('EXPOSE', 'name', null, ['valueString' => $a['valueString']]);
        $this->psSettingsRepository->fillFromArray($e, $a);

        return $e;
    }

}