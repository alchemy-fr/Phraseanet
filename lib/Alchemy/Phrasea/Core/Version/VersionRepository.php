<?php

namespace Alchemy\Phrasea\Core\Version;

use Alchemy\Phrasea\Core\Version;

interface VersionRepository
{

    const DEFAULT_VERSION = '0.0.0';

    /**
     * @return string
     */
    public function getVersion();

    /**
     * @param Version $version
     * @return bool
     */
    public function saveVersion(Version $version);
}
