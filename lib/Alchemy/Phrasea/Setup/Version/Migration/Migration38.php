<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Symfony\Component\Yaml\Yaml;

class Migration38 implements MigrationInterface
{
    private $app;
    private $yaml;

    private $connexionsYaml;
    private $binariesYaml;
    private $servicesYaml;
    private $configYaml;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->connexionsYaml = $app['root.path'] . '/config/connexions.yml';
        $this->binariesYaml = $app['root.path'] . '/config/binaries.yml';
        $this->servicesYaml = $app['root.path'] . '/config/services.yml';
        $this->configYaml = $app['root.path'] . '/config/config.yml';
    }

    public function migrate()
    {
        if (!file_exists($this->configYaml)
            || !file_exists($this->servicesYaml)
            || !file_exists($this->connexionsYaml)) {
            throw new \LogicException('Required config files not found');
        }

        $this->yaml = new Yaml();

        $this->doMigrate($this->app);
    }

    private function doMigrate($app)
    {
        $app['configuration']->initialize();
        $conf = $app['configuration']->getConfig();

        $this->migrateConnexions($conf);
        $this->migrateConfigAndServices($conf);
        $this->migrateBinaries($conf);

        $app['configuration']->setConfig($conf);

        foreach (array(
            $this->configYaml,
            $this->connexionsYaml,
            $this->binariesYaml,
            $this->servicesYaml
        ) as $file) {
            if (is_file($file)) {
                rename($file, $file.'.bkp');
            }
        }
    }

    private function migrateBinaries(array &$conf)
    {
        if (is_file($this->binariesYaml)) {
            $binaries = $this->yaml->parse($this->binariesYaml);
            foreach ($binaries['binaries'] as $key => $value) {
                $conf['binaries'][$key] = $value;
            }
        }
    }

    private function migrateConfigAndServices(array &$conf)
    {
        $opcodeCacheService = $cacheService = null;

        if (is_file($this->configYaml)) {
            $data = $this->yaml->parse($this->configYaml);
            $conf['main']['key'] = $data['key'];
            $env = $data['environment'];
            if (isset($data[$env])) {
                $conf['main']['servername'] = $data[$env]['phraseanet']['servername'];
                $conf['main']['maintenance'] = $data[$env]['phraseanet']['maintenance'];
                $opcodeCacheService = $data[$env]['opcodecache'];
                $cacheService = $data[$env]['cache'];
            }
        }

        if (is_file($this->servicesYaml)) {
            $services = $this->yaml->parse($this->servicesYaml);
            if (null !== $opcodeCacheService) {
                $conf['main']['opcodecache']['type'] = str_replace('Cache\\', '', $services['Cache'][$opcodeCacheService]['type']);
                if (isset($services['Cache'][$opcodeCacheService]['options'])) {
                    $conf['main']['opcodecache']['options'] = $services['Cache'][$opcodeCacheService]['options'];
                } else {
                    $conf['main']['opcodecache']['options'] = array();
                }
            }
            if (null !== $cacheService) {
                $conf['main']['cache']['type'] = str_replace('Cache\\', '', $services['Cache'][$cacheService]['type']);
                if (isset($services['Cache'][$cacheService]['options'])) {
                    $conf['main']['cache']['options'] = $services['Cache'][$cacheService]['options'];
                } else {
                    $conf['main']['cache']['options'] = array();
                }
            }
            $conf['border-manager'] = $services['Border']['border_manager']['options'];
        }
    }

    private function migrateConnexions(array &$conf)
    {
        if (is_file($this->connexionsYaml)) {
            $data = $this->yaml->parse($this->connexionsYaml);

            $conf['main']['database'] = $data['main_connexion'];
            $conf['main']['database-test'] = $data['test_connexion'];
        }
    }
}
