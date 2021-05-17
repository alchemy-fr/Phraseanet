<?php

namespace Alchemy\Phrasea\Model\Repositories\PsSettings\Expose;

use Alchemy\Phrasea\Model\Entities\PsSettings;
use Alchemy\Phrasea\Model\Repositories\PsSettingKeysRepository;
use Alchemy\Phrasea\Model\Repositories\PsSettings\App;
use Alchemy\Phrasea\Model\Repositories\PsSettingsRepository;

class Instance extends App
{
    private $frontUri = null;
    private $clientId = null;

    public function __construct(PsSettingsRepository $psSettingsRepository, PsSettingKeysRepository $psSettingKeysRepository, PsSettings $instanceEntity)
    {
        parent::__construct($psSettingsRepository, $psSettingKeysRepository, $instanceEntity);

        foreach($this->getSettings() as $e) {
            switch ($e->getName()) {
                case 'front_uri':
                    $this->frontUri = $e->getValueString();
                    break;
                case 'client_id':
                    $this->clientId = $e->getValueString();
                    break;
                default:
                    // unknown setting ? ignore
                    break;
            }
        }
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
        $this->getOrSetSetting('front_uri', ['valueString' => $frontUri]);
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
        $this->getOrSetSetting('client_id', ['valueString' => $clientId]);
    }

    public function canSee(int $userId, $value = null)
    {
        return $this->getOrSetACE($userId, 'cansee', $value);
    }

    public function canAdd(int $userId, $value = null)
    {
        return $this->getOrSetACE($userId, 'canadd', $value);
    }
}