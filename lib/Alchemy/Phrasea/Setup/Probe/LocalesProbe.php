<?php

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;

class LocalesProbe extends LocalesRequirements implements ProbeInterface
{
    public function __construct($locale)
    {
        parent::__construct($locale);
    }

    /**
     * {@inheritdoc}
     *
     * @return LocalesProbe
     */
    public static function create(Application $app)
    {
        return new static($app['locale']);
    }
}
