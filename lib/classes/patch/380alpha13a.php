<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_380alpha13a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.13';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

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
    public function apply(base $appbox, Application $app)
    {
        $sql = 'SELECT `key`, `value` FROM `registry`
                WHERE `key` = "GV_X_Accel_Redirect"
                    OR `key` = "GV_X_Accel_Redirect_mount_point"
                    OR `key` = "GV_modxsendfile"';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $registry = [
            'GV_X_Accel_Redirect' => null,
            'GV_X_Accel_Redirect_mount_point' => null,
            'GV_modxsendfile' => null,
        ];

        foreach ($rows as $row) {
            $registry[$row['key']] = $row['value'];
        }

        $xsendfilePath = $registry['GV_X_Accel_Redirect'];
        $xsendfileMountPoint = $registry['GV_X_Accel_Redirect_mount_point'];

        $config = $app['configuration.store']->setDefault('xsendfile')->getConfig();

        $config['xsendfile']['enabled'] = (Boolean) $registry['GV_modxsendfile'];
        $config['xsendfile']['type'] = $config['xsendfile']['enabled'] ? 'nginx' : '';

        if (null !== $xsendfilePath && null !== $xsendfileMountPoint) {
            $config['xsendfile']['mapping'] = [[
                'directory' => $xsendfilePath,
                'mount-point' => $xsendfileMountPoint,
            ]];
        }

        $app['configuration.store']->setConfig($config);

        $toRemove = ['GV_X_Accel_Redirect', 'GV_X_Accel_Redirect_mount_point', 'GV_modxsendfile'];

        $sql = 'DELETE FROM registry WHERE `key` = :k';
        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($toRemove as $registryKey) {
            $stmt->execute([
                ':k' => $registryKey
            ]);
        }
        $stmt->closeCursor();

        return true;
    }
}
