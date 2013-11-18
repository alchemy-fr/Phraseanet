<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Task;

class patch_390alpha10a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.10';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

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
        return false;
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
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $this->upgradeConf($app);
        $this->upgradeRegistry($app);
    }

    private function upgradeRegistry(Application $app)
    {
        $sql = 'SELECT `key`, `value`, `type` FROM registry';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $registry = array();

        foreach ($rows as $row) {
            switch ($row['type']) {
                case 'boolean':
                    $value = (Boolean) $row['value'];
                    break;
                case 'integer':
                    $value = (int) $row['value'];
                    break;
                case 'enum':
                case 'string':
                case 'text':
                case 'timezone':
                    $value = $row['value'];
                    break;
                case 'binary':
                case 'enum_multi':
                    continue;
                    break;
            }

            $registry[$row['key']] = $value;
        }

        $config = $app['configuration']->getConfig();

        $config['languages']['default'] = isset($registry['GV_default_lng']) ? $registry['GV_default_lng'] : 'fr_FR';

        $app['configuration']->setConfig($config);
    }

    private function upgradeConf(Application $app)
    {
        $config = $app['configuration']->getConfig();

        if (isset($config['main']['languages'])) {
            $config = array_merge(array('languages' => array('available' => $config['main']['languages'])), $config);
            unset($config['main']['languages']);
        }

        $config = array_merge(array('servername' => $config['main']['servername']), $config);
        unset($config['main']['servername']);

        $config['main']['task-manager'] = $config['task-manager'];
        unset($config['task-manager']);

        if (isset($config['binaries'])) {
            $binaries = isset($config['main']['binaries']) ? $config['main']['binaries'] : array();
            $config['main']['binaries'] = array_merge($binaries, $config['binaries']);
            unset($config['binaries']);
        }

        $app['configuration']->setConfig($config);
    }
}
