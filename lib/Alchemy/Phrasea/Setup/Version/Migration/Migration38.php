<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Exception\RuntimeException;

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
        $app['configuration.store']->initialize();
        $conf = $app['configuration.store']->getConfig();

        $this->migrateConnexions($conf);
        $this->migrateConfigAndServices($conf);
        $this->migrateBinaries($conf);

        $app['configuration.store']->setConfig($conf);

        foreach ([
            $this->configYaml,
            $this->connexionsYaml,
            $this->binariesYaml,
            $this->servicesYaml
        ] as $file) {
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
                $conf['main']['binaries'][$key] = $value;
            }
        }
    }

    private function migrateConfigAndServices(array &$conf)
    {
        $opcodeCacheService = $cacheService = null;

        if (is_file($this->configYaml)) {
            $data = $this->yaml->parse($this->configYaml);
            $key = isset($data['key']) ? $data['key'] : $this->fetchInstanceKey();
            $conf['main']['key'] = $key;
            $env = $data['environment'];
            if (isset($data[$env])) {
                $conf['main']['servername'] = $data[$env]['phraseanet']['servername'];
                $conf['main']['maintenance'] = $data[$env]['phraseanet']['maintenance'];
                $cacheService = $data[$env]['cache'];
            }
        }

        if (is_file($this->servicesYaml)) {
            $services = $this->yaml->parse($this->servicesYaml);
            if (null !== $cacheService) {
                $conf['main']['cache']['type'] = str_replace('Cache\\', '', $services['Cache'][$cacheService]['type']);
                if (isset($services['Cache'][$cacheService]['options'])) {
                    $conf['main']['cache']['options'] = $services['Cache'][$cacheService]['options'];
                } else {
                    $conf['main']['cache']['options'] = [];
                }
            }
            if (isset($services['Border'])) {
                $conf['border-manager'] = $services['Border']['border_manager']['options'];
            }
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

    private function fetchInstanceKey()
    {
        $stmt = $this->app->getApplicationBox()->get_connection()->prepare('SELECT `value` FROM registry WHERE `key` = "GV_sit"');
        $stmt->execute();
        $rs = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$rs) {
            throw new RuntimeException('Unable to fetch GV_SIT key from registry table.');
        }

        return $rs['key'];
    }
}
