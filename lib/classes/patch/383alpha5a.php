<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_383alpha5a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.3-alpha.5';

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
    public function getDoctrineMigrations()
    {
        return [];
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
    public function apply(\appbox $appbox, Application $app)
    {
        $config = $app['phraseanet.configuration']->getConfig();

        $config['main']['task-manager']['logger'] = [
            'enabled'   => true,
            'max-files' => 10,
            'level'     => 'INFO',
        ];

        $app['phraseanet.configuration']->setConfig($config);

        return true;
    }
}
