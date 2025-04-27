<?php

namespace Alchemy\Phrasea\Model\Repositories\PsSettings\Expose;

use Alchemy\Phrasea\Model\Entities\PsSettings;
use Alchemy\Phrasea\Model\Repositories\PsSettingKeysRepository;
use Alchemy\Phrasea\Model\Repositories\PsSettings\App;
use Alchemy\Phrasea\Model\Repositories\PsSettingsRepository;

/*

"app" is a class that covers any kind of phraseanet service (ps) application, like "expose" or "uploader"
app provides helpers like "ace" (acl) that may be usefull to all kind of ps applications.

here we implement a class to wrap "expose" settings

for now we store only 2 settings for a expose instance : "frontUri" and "clientId"
we can also set acl for "canSee" and "canAdd"

*/

class Instance extends App
{

    /**
     * stored into "valueString" because varchar(255) should be long enough
     * @var string|null
     */
    private $frontUri = null;

    /**
     * stored into "valueString" because varchar(255) should be long enough
     * @var string|null
     */
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