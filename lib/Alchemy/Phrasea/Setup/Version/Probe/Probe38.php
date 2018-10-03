<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Migration\Migration38;

class Probe38 implements ProbeInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function isMigrable()
    {
        return is_file($this->app['root.path'] . "/config/config.yml")
            && is_file($this->app['root.path'] . "/config/services.yml")
            && is_file($this->app['root.path'] . "/config/connexions.yml");
    }

    /**
     * {@inheritdoc}
     */
    public function getMigration()
    {
        return new Migration38($this->app);
    }
}
