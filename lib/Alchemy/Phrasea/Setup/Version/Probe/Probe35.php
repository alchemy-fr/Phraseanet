<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Migration\Migration35;

class Probe35 implements ProbeInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isMigrable()
    {
        /**
         * We can not use registry to inject a path as the install is not yet done
         */
        return is_file(__DIR__ . "/../../../../../../config/connexion.inc")
            && is_file(__DIR__ . "/../../../../../../config/config.inc");
    }

    public function getMigration()
    {
        return new Migration35($this->app);
    }
}
