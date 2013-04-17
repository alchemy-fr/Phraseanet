<?php

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;

class PhpProbe extends PhpRequirements implements ProbeInterface
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
