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
     * @return string|null
     */
    public function getFrontUri()
    {
        return $this->frontUri;
    }

    public function setFrontUri($frontUri)
    {
        $this->frontUri = $frontUri;
        $this->setSetting('front_uri', ['valueText' => $frontUri]);
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
        $this->setSetting('client_id', ['valueText' => $clientId]);
    }

    public function canSee(int $userId, $value = null)
    {
        return $this->readOrSetACE($userId, 'cansee', $value);
    }

    public function canAdd(int $userId, $value = null)
    {
        return $this->readOrSetACE($userId, 'canadd', $value);
    }
}