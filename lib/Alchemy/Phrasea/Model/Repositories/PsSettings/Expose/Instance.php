<?php

namespace Alchemy\Phrasea\Model\Repositories\PsSettings\Expose;

use Alchemy\Phrasea\Model\Entities\PsSettings;
use Alchemy\Phrasea\Model\Repositories\PsSettingsRepository;

class Instance
{
    /** @var PsSettingsRepository  */
    private $psSettingsRepository;

    /** @var PsSettings  */
    private $instanceEntity;

    private $frontUri = null;
    private $clientId = null;

    public function __construct(PsSettingsRepository $psSettingsRepository, PsSettings $instanceEntity)
    {
        $this->psSettingsRepository = $psSettingsRepository;
        $this->instanceEntity = $instanceEntity;

        foreach($this->psSettingsRepository->get('EXPOSE_SETTING', null, $instanceEntity) as $e) {
            switch ($e->getName()) {
                case 'front_uri':
                    $this->frontUri = $e->getValueText();
                    break;
                case 'client_id':
                    $this->clientId = $e->getValueText();
                    break;
                default:
                    // unknown setting ? ignore
                    break;
            }
        }
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

    /**
     * @return string|null
     */
    public function getFrontUri()
    {
        return $this->frontUri;
    }

    public function setFrontUri($frontUri)
    {
        $this->frontUri = $frontUri;
        return $this->setValueText('EXPOSE_SETTING', 'front_uri', $this->instanceEntity, $frontUri);
    }

    private function setValueText(string $role, string $name, PsSettings $parent, string $value)
    {
        $e = $this->psSettingsRepository->getOrCreateUnique($role, $name, $parent);
        $e->setValueText($value);
        $this->psSettingsRepository->getEntityManager()->persist($e);
        $this->psSettingsRepository->getEntityManager()->flush();

        return $e;
    }

    /**
     * @return string|null
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this->setValueText('EXPOSE_SETTING', 'client_id', $this->instanceEntity, $clientId);
    }
}