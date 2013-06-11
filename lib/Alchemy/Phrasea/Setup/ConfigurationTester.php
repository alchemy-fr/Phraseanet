<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Probe\Probe31;
use Alchemy\Phrasea\Setup\Version\Probe\Probe35;
use Alchemy\Phrasea\Setup\Version\Probe\ProbeInterface as VersionProbeInterface;
use Alchemy\Phrasea\Setup\Probe\BinariesProbe;
use Alchemy\Phrasea\Setup\Probe\CacheServerProbe;
use Alchemy\Phrasea\Setup\Probe\OpcodeCacheProbe;
use Alchemy\Phrasea\Setup\Probe\FilesystemProbe;
use Alchemy\Phrasea\Setup\Probe\LocalesProbe;
use Alchemy\Phrasea\Setup\Probe\PhpProbe;
use Alchemy\Phrasea\Setup\Probe\PhraseaProbe;
use Alchemy\Phrasea\Setup\Probe\SearchEngineProbe;
use Alchemy\Phrasea\Setup\Probe\SystemProbe;

class ConfigurationTester
{
    private $app;
    private $requirements;
    private $versionProbes;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->versionProbes = array(
            new Probe31($this->app),
            new Probe35($this->app),
        );
    }

    public function getRequirements()
    {
        if ($this->requirements) {
            return $this->requirements;
        }

        $this->requirements = array(
            BinariesProbe::create($this->app),
            CacheServerProbe::create($this->app),
            OpcodeCacheProbe::create($this->app),
            FilesystemProbe::create($this->app),
            LocalesProbe::create($this->app),
            PhpProbe::create($this->app),
            PhraseaProbe::create($this->app),
            SearchEngineProbe::create($this->app),
            SystemProbe::create($this->app),
        );

        return $this->requirements;
    }

    public function registerVersionProbe(VersionProbeInterface $probe)
    {
        $this->versionProbes[] = $probe;
    }

    /**
     * Return true if got the latest configuration file.
     *
     * @return type
     */
    public function isInstalled()
    {
        return $this->app['phraseanet.configuration']->isSetup();
    }

    /**
     *
     */
    public function isUpToDate()
    {
        return $this->isInstalled() && !$this->isUpgradable();
    }

    /**
     *
     */
    public function isBlank()
    {
        return !$this->isInstalled() && !$this->isMigrable();
    }

    /**
     *
     * @return boolean
     */
    public function isUpgradable()
    {
        if (!$this->isInstalled()) {
            return false;
        }

        $upgradable = version_compare($this->app['phraseanet.appbox']->get_version(), $this->app['phraseanet.version']->getNumber(), ">");

        if (!$upgradable) {
            foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
                if (version_compare($databox->get_version(), $this->app['phraseanet.version']->getNumber(), "<")) {
                    $upgradable = true;
                    break;
                }
            }
        }

        return $upgradable;
    }

    /**
     * Returns true if a major migration script can be executed
     *
     * @return type
     */
    public function isMigrable()
    {
        return (Boolean) $this->getMigrations();
    }

    public function getMigrations()
    {
        $migrations = array();

        if ($this->isUpToDate()) {
            return $migrations;
        }

        foreach ($this->versionProbes as $probe) {
            if ($probe->isMigrable()) {
                $migrations[] = $probe->getMigration();
            }
        }

        return $migrations;
    }
}
