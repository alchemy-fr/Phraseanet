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

/*
        // here we read instances (s1) and settings (s2) in one op to hydrate complete Expose/Instance objects
        // so we don't need some "InstanceSettings" object
        // nb : the "order by" garantee that "EXPOSE" row comes before "EXPOSE_SETTINGS" siblings
        $dql = "SELECT s1, s2\n"
            . " FROM Phraseanet:PsSettings s1\n"
            . " JOIN Phraseanet:PsSettings s2 WITH s2.parent=s1.id"
            . " WHERE s1.role = 'EXPOSE' AND s2.role = 'EXPOSE_SETTING'\n"
            . " ORDER BY s2.parent, s2.id";

        $query = $this->em->createQuery($dql);

        $r = [];
        $tmp = [];
        /** @var PsSettings $p * /
        foreach($query->getResult() as $p) {
            if($p->getRole() === 'EXPOSE') {
                $tmp[$p->getId()] = [
                    'instanceEntity' => $p,
                    'settingEntities' => []
                ];
            }
            else {
                $tmp[$p->getParent()->getId()]['settingEntities'][$p->getId()] = $p;
            }
        }

        foreach($tmp as $instance) {
            $r[]  = new Instance($this->em, $this->psSettingsRepository, $instance);
        }

        return $r;
*/
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