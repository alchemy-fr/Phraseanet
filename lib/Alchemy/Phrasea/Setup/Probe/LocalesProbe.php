<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
