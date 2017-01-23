<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Core\Connection\ConnectionSettings;

class SetupParameters
{
    /**
     * @var ConnectionSettings
     */
    private $appboxSettings;

    /**
     * @var ConnectionSettings
     */
    private $databoxSettings;

    /**
     * @return ConnectionSettings
     */
    public function getAppboxSettings()
    {
        return $this->appboxSettings;
    }

    /**
     * @return ConnectionSettings
     */
    public function getDataboxSettings()
    {
        return $this->databoxSettings;
    }
}
