<?php

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\PhraseaRequirements;

class PhraseaProbe extends PhraseaRequirements implements ProbeInterface
{
    /**
     * {@inheritdoc}
     *
     * @return PhraseaProbe
     */
    public static function create(Application $app)
    {
        return new static();
    }
}
