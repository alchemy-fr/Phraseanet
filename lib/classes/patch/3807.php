<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Yaml\Yaml;

class patch_3807 implements patchInterface
{
    /** @var string */
    private $release = '3.8.0.a7';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);
    private $yaml;

    private $connexionsYaml;
    private $binariesYaml;
    private $servicesYaml;
    private $configYaml;

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $this->yaml = new Yaml();

        $this->connexionsYaml = $app['root.path'] . '/config/connexions.yml';
        $this->binariesYaml = $app['root.path'] . '/config/binaries.yml';
        $this->servicesYaml = $app['root.path'] . '/config/services.yml';
        $this->configYaml = $app['root.path'] . '/config/config.yml';

        $this->migrate($app);

        return true;
    }

    private function migrate($app)
    {
        $conf = $app['phraseanet.configuration']->getConfig();

        $this->migrateConnexions($conf);
        $this->migrateConfigAndServices($conf);
        $this->migrateBinaries($conf);

        $app['phraseanet.configuration']->setConfig($conf);

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
