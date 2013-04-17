<?php

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\System\RequirementCollectionInterface;

interface ProbeInterface extends RequirementCollectionInterface
{
    /**
     * @param Application $app
     */
    public static function create(Application $app);
}
