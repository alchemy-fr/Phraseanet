<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Setup\Probe\BinariesProbe;
use Alchemy\Phrasea\Setup\Probe\CacheServerProbe;
use Alchemy\Phrasea\Setup\Probe\DataboxStructureProbe;
use Alchemy\Phrasea\Setup\Probe\FilesystemProbe;
use Alchemy\Phrasea\Setup\Probe\LocalesProbe;
use Alchemy\Phrasea\Setup\Probe\PhpProbe;
use Alchemy\Phrasea\Setup\Probe\SearchEngineProbe;
use Alchemy\Phrasea\Setup\Probe\SubdefsPathsProbe;
use Alchemy\Phrasea\Setup\Probe\SystemProbe;

class CheckConfig extends AbstractCheckCommand
{
    const CHECK_OK = 0;
    const CHECK_WARNING = 1;
    const CHECK_ERROR = 2;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription("Performs a check against the environment and configuration. Give some advices for production settings.");

        return $this;
    }

    protected function provideRequirements()
    {
        return [
            BinariesProbe::create($this->container),
            CacheServerProbe::create($this->container),
            DataboxStructureProbe::create($this->container),
            FilesystemProbe::create($this->container),
            LocalesProbe::create($this->container),
            PhpProbe::create($this->container),
            SearchEngineProbe::create($this->container),
            SubdefsPathsProbe::create($this->container),
            SystemProbe::create($this->container),
        ];
    }
}
