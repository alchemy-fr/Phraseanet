<?php

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;

class SystemProbe extends SystemRequirements implements ProbeInterface
{
    /**
     * {@inheritdoc}
     *
     * @return SystemProbe
     */
    public static function create(Application $app)
    {
        return new static();
    }
}
