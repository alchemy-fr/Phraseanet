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

class patch_3813 implements patchInterface
{
    /** @var string */
    private $release = '3.8.0.a13';

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
    public function apply(base $appbox, Application $app)
    {
        $xsendfilePath = $app['phraseanet.registry']->get('GV_X_Accel_Redirect');
        $xsendfileMountPoint = $app['phraseanet.registry']->get('GV_X_Accel_Redirect_mount_point');

        $config = $app['phraseanet.configuration']->setDefault('xsendfile')->getConfig();

        $config['xsendfile']['enable'] = (Boolean) $app['phraseanet.registry']->get('GV_modxsendfile', false);

        if (null !== $xsendfilePath && null !== $xsendfileMountPoint) {
            $config['xsendfile']['mapping'][0] = array(
                'directory' => $xsendfilePath,
                'mount-point' => $xsendfileMountPoint,
            );
        }

        $app['phraseanet.configuration']->setConfig($config);

        $toRemove = array('GV_X_Accel_Redirect', 'GV_X_Accel_Redirect_mount_point', 'GV_modxsendfile');

        $sql = 'DELETE FROM registry WHERE key = :k';
        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($toRemove as $registryKey) {
            $stmt->execute(array(
                ':k' => $registryKey
            ));
        }
        $stmt->closeCursor();

        return true;
    }
}
