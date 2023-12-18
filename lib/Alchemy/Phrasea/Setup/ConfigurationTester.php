<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Migration\MigrationInterface;
use Alchemy\Phrasea\Setup\Version\Probe\Probe31;
use Alchemy\Phrasea\Setup\Version\Probe\Probe35;
use Alchemy\Phrasea\Setup\Version\Probe\Probe38;
use Alchemy\Phrasea\Setup\Version\Probe\ProbeInterface;
use Alchemy\Phrasea\Setup\Version\Probe\ProbeInterface as VersionProbeInterface;
use Alchemy\Phrasea\Setup\Probe\BinariesProbe;
use Alchemy\Phrasea\Setup\Probe\CacheServerProbe;
use Alchemy\Phrasea\Setup\Probe\DataboxStructureProbe;
use Alchemy\Phrasea\Setup\Probe\FilesystemProbe;
use Alchemy\Phrasea\Setup\Probe\LocalesProbe;
use Alchemy\Phrasea\Setup\Probe\PhpProbe;
use Alchemy\Phrasea\Setup\Probe\SearchEngineProbe;
use Alchemy\Phrasea\Setup\Probe\SubdefsPathsProbe;
use Alchemy\Phrasea\Setup\Probe\SystemProbe;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\version;

class ConfigurationTester
{
    private $app;
    private $requirements;

    /** @var ProbeInterface[] */
    private $versionProbes;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->versionProbes = [
            new Probe31($this->app),
            new Probe35($this->app),
            new Probe38($this->app),
        ];
    }

    public function getRequirements()
    {
        if ($this->requirements) {
            return $this->requirements;
        }

        $this->requirements = [
            BinariesProbe::create($this->app),
            CacheServerProbe::create($this->app),
            DataboxStructureProbe::create($this->app),
            FilesystemProbe::create($this->app),
            LocalesProbe::create($this->app),
            PhpProbe::create($this->app),
            SearchEngineProbe::create($this->app),
            SubdefsPathsProbe::create($this->app),
            SystemProbe::create($this->app),
        ];

        return $this->requirements;
    }

    public function registerVersionProbe(VersionProbeInterface $probe)
    {
        $this->versionProbes[] = $probe;
    }

    /**
     * Return true if got the latest configuration file.
     *
     * @return bool
     */
    public function isInstalled()
    {
        return $this->app['configuration.store']->isSetup();
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

        $upgradable = version::lt($this->app->getApplicationBox()->get_version(), $this->app['phraseanet.version']->getNumber());

        if (!$upgradable) {
            foreach ($this->app->getDataboxes() as $databox) {
                if (version::lt($databox->get_version(), $this->app['phraseanet.version']->getNumber())) {
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
     * @return bool
     */
    public function isMigrable()
    {
        return (Boolean) $this->getMigrations();
    }

    public function isConnectedToDBHost()
    {
        $connectionConfig = $this->app['conf']->get(['main', 'database']);
        /** @var Connection $connection */
        $connection = $this->app['db.provider']($connectionConfig);

        try {
            $connection->connect();

            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * @return MigrationInterface[]
     */
    public function getMigrations(InputInterface $input = null, OutputInterface $output = null)
    {
       $migrations = [];

        if ($this->isUpToDate()) {
            return $migrations;
        }

        foreach ($this->versionProbes as $probe) {
            if($output) {
                $output->writeln(sprintf("configurationTester : probing \"%s\"", get_class($probe)));
            }
            if ($probe->isMigrable()) {
                $migrations[] = $probe->getMigration();
            }
        }

        return $migrations;
    }
}
